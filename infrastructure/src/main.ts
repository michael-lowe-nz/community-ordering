import {
  App, aws_ecs,
  aws_ecs_patterns,
  aws_secretsmanager,
  Duration,
  Stack, StackProps
} from 'aws-cdk-lib';
import { Construct } from 'constructs';
import path from 'path';
import { HobbyVPC } from './constructs/hobbyVPC';

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
      }
    });

    googleAPIKey.grantRead(ecsTask.taskDefinition.taskRole);
  }
}

// for development, use account/region from cdk cli
const devEnv = {
  account: process.env.CDK_DEFAULT_ACCOUNT,
  region: process.env.CDK_DEFAULT_REGION,
};

const app = new App();

new ApplicationStack(app, 'laravel-starter-dev', { env: devEnv });
// new MyStack(app, 'laravel-starter-prod', { env: prodEnv });

app.synth();