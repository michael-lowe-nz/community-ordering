import {
  App, aws_certificatemanager, aws_ec2, aws_ecs,
  aws_ecs_patterns,
  aws_elasticloadbalancingv2,
  // aws_efs,
  aws_iam,
  aws_route53,
  aws_route53_targets,
  aws_secretsmanager,
  Duration,
  Stack, StackProps
} from 'aws-cdk-lib';
import { Construct } from 'constructs';
import path from 'path';
import { HobbyVPC } from './constructs/hobbyVPC';
import { GitHubActionRole } from "cdk-pipelines-github";

export class NetworkStack extends Stack {
  cluster: aws_ecs.Cluster;
  vpc: aws_ec2.Vpc;
  constructor(scope: Construct, id: string, props: StackProps = {}) {
    super(scope, id, props);

    this.vpc = new HobbyVPC(this, 'Network').vpc;
    
    this.cluster = new aws_ecs.Cluster(this, 'Cluster', {
      vpc: this.vpc,
    });
  }
}

interface ApplicationStackProps extends StackProps {
  vpc: aws_ec2.Vpc;
  cluster: aws_ecs.Cluster;
}

export class ApplicationStack extends Stack {
  constructor(scope: Construct, id: string, props: ApplicationStackProps) {
    super(scope, id, props);

    // Add a hosted zone for orderfood.site
    const hostedZone = new aws_route53.HostedZone(this, 'HostedZone', {
      zoneName: 'orderfood.site',
    });

    // Create an ACM certificate for the domain
    const certificate = new aws_certificatemanager.Certificate(this, 'Certificate', {
      domainName: 'orderfood.site',
      validation: aws_certificatemanager.CertificateValidation.fromDns(hostedZone),
    });

    const googleAPIKey = aws_secretsmanager.Secret.fromSecretNameV2(this, 'GooglePlacesAPIKey', 'GooglePlacesAPIKey');

    const ecsTask = new aws_ecs_patterns.ApplicationLoadBalancedFargateService(this, 'Laravel', {
      cluster: props.cluster,
      circuitBreaker: { rollback: true },
      minHealthyPercent: 0,
      maxHealthyPercent: 100,
      certificate,
      // sslPolicy: Ssl.RECOMMENDED,
      domainName: 'orderfood.site',
      domainZone: hostedZone,
      redirectHTTP: true,
      protocol: aws_elasticloadbalancingv2.ApplicationProtocol.HTTPS,
      taskImageOptions: {
        image: aws_ecs.ContainerImage.fromAsset(path.join(__dirname, '../../')),
        environment: {
          "APP_DEBUG": "true",
          "APP_KEY": "base64:DxoxkrxQSnbiAy2HFg2Q3lZP7TPOj6ZO/Ipajr5UvGk=",
          "LOG_CHANNEL": "stderr",
        },
        secrets: {
          "GOOGLE_PLACES_API_KEY": aws_ecs.Secret.fromSecretsManager(googleAPIKey),},
        logDriver: aws_ecs.LogDrivers.awsLogs({ streamPrefix: 'Laravel' }),
      },
      runtimePlatform: {
        cpuArchitecture: aws_ecs.CpuArchitecture.ARM64,
        operatingSystemFamily: aws_ecs.OperatingSystemFamily.LINUX,
      },
      cpu: 256,
      memoryLimitMiB: 512,
      desiredCount: 1,
      publicLoadBalancer: true, // Default is true
      enableExecuteCommand: true,
      // Add health check configuration
      healthCheckGracePeriod: Duration.seconds(60),  // Give containers time to start up
      healthCheck: {
        command: ["php artisan db:monitor"],
      },
    });

    // Create DNS records for the load balancer
    new aws_route53.ARecord(this, 'AliasRecord', {
      zone: hostedZone,
      target: aws_route53.RecordTarget.fromAlias(new aws_route53_targets.LoadBalancerTarget(ecsTask.loadBalancer)),
      recordName: 'orderfood.site',
    });

    googleAPIKey.grantRead(ecsTask.taskDefinition.taskRole);

    ecsTask.targetGroup.configureHealthCheck({
      path: '/health-check',
      interval: Duration.seconds(60),
      healthyThresholdCount: 2,
      unhealthyThresholdCount: 5,
      timeout: Duration.seconds(30),
    })

  }
}

export class OIDCStack extends Stack {
  constructor(scope: Construct, id: string, props: StackProps = {}) {
    super(scope, id, props);

    new aws_iam.OpenIdConnectProvider(
      this,
      "GithubOIDCProvider",
      {
        url: "https://token.actions.githubusercontent.com",
        clientIds: ["sts.amazonaws.com"],
        thumbprints: [
          "6938fd4d98bab03faadb97b34396831e3780aea1",
          "1c58a3a8518e8759bf075b76b750d4f2df264fcd",
        ],
      }
    );
  }
}

export class SupportStack extends Stack {
  constructor(scope: Construct, id: string, props: StackProps = {}) {
    super(scope, id, props);


    const githubProvider =
      aws_iam.OpenIdConnectProvider.fromOpenIdConnectProviderArn(
        this,
        "OpenIdConnectProvider",
        `arn:aws:iam::${this.account}:oidc-provider/token.actions.githubusercontent.com`
      );

    new GitHubActionRole(this, "Deployment", {
      repos: ["michael-lowe-nz/community-ordering"],
      provider: githubProvider,
      roleName: "community-ordering-deployment-role",
    });
  }
}

// for development, use account/region from cdk cli
const devEnv = {
  account: process.env.CDK_DEFAULT_ACCOUNT,
  region: process.env.CDK_DEFAULT_REGION,
};

const app = new App();

const networkStack = new NetworkStack(app, 'CommunityOrdering-Network', { env: devEnv });
new ApplicationStack(app, 'CommunityOrdering-Application', { env: devEnv, vpc: networkStack.vpc, cluster: networkStack.cluster });

if (process.env.SETUP_ONLY) {
  new SupportStack(app, 'CommunityOrdering-Support', { env: devEnv });
  new OIDCStack(app, 'OIDC', { env: devEnv });
}

app.synth();