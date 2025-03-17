import {
  App, aws_ecs,
  aws_ecs_patterns,
  aws_iam,
  aws_secretsmanager,
  Duration,
  Stack, StackProps
} from 'aws-cdk-lib';
import { Construct } from 'constructs';
import path from 'path';
import { HobbyVPC } from './constructs/hobbyVPC';
import { GitHubActionRole } from "cdk-pipelines-github";

export class ApplicationStack extends Stack {
  constructor(scope: Construct, id: string, props: StackProps = {}) {
    super(scope, id, props);

    const network = new HobbyVPC(this, 'Network');

    const cluster = new aws_ecs.Cluster(this, 'Cluster', {
      vpc: network.vpc,
    });

    const googleAPIKey = aws_secretsmanager.Secret.fromSecretNameV2(this, 'GooglePlacesAPIKey', 'GooglePlacesAPIKey');

    const ecsTask = new aws_ecs_patterns.ApplicationLoadBalancedFargateService(this, 'Laravel', {
      cluster: cluster,
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

    googleAPIKey.grantRead(ecsTask.taskDefinition.taskRole);
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

new ApplicationStack(app, 'CommunityOrdering-Application', { env: devEnv });

if (!process.env.CI) {
  new SupportStack(app, 'CommunityOrdering-Support', { env: devEnv });
  new OIDCStack(app, 'OIDC', { env: devEnv });
}

app.synth();