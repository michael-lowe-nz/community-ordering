import { App, aws_ec2, aws_ecs, aws_ecs_patterns, Stack, StackProps } from 'aws-cdk-lib';
import { Construct } from 'constructs';
import path from 'path';
import { SingleAzVpcWithNat } from './constructs/hobbyVPC';

export class ApplicationStack extends Stack {
  constructor(scope: Construct, id: string, props: StackProps = {}) {
    super(scope, id, props);

    const network = new SingleAzVpcWithNat(this, 'Network');

    const cluster = new aws_ecs.Cluster(this, 'Cluster', {
      vpc: network.vpc,
    });

    // A VPC with 1 AZ, and a NAT instance


    // Create a load-balanced Fargate service and make it public
    new aws_ecs_patterns.ApplicationLoadBalancedFargateService(this, 'Laravel', {
      cluster: cluster,
      
      taskImageOptions: {
        image: aws_ecs.ContainerImage.fromAsset(path.join(__dirname, '../../')),
        environment: {
          "APP_DEBUG": "true",
        }
      },
      runtimePlatform: {
        cpuArchitecture: aws_ecs.CpuArchitecture.ARM64,
        operatingSystemFamily: aws_ecs.OperatingSystemFamily.LINUX,
      },
      cpu: 256,
      memoryLimitMiB: 512,
      desiredCount: 1,
      publicLoadBalancer: true, // Default is true
      enableExecuteCommand: true
    });
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