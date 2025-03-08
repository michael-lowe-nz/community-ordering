import { App, aws_ec2, aws_ecs, aws_ecs_patterns, Stack, StackProps } from 'aws-cdk-lib';
import { Construct } from 'constructs';
import path from 'path';

export class ApplicationStack extends Stack {
  constructor(scope: Construct, id: string, props: StackProps = {}) {
    super(scope, id, props);

     // define resources here...
     const vpc = new aws_ec2.Vpc(this, 'Network', {
      maxAzs: 2,
    });

    const cluster = new aws_ecs.Cluster(this, 'Cluster', {
      vpc: vpc,
    });

    // Create a load-balanced Fargate service and make it public
    new aws_ecs_patterns.ApplicationLoadBalancedFargateService(this, 'Laravel', {
      cluster: cluster,
      taskImageOptions: { image: aws_ecs.ContainerImage.fromAsset(path.join(__dirname, '../laravel-app')) },
      publicLoadBalancer: true, // Default is true
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