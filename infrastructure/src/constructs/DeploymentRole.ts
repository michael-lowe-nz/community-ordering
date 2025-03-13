import * as cdk from 'aws-cdk-lib';
import { Construct } from 'constructs';

export interface DeploymentRoleProps {
  /**
   * The GitHub repository name in the format owner/repo
   */
  readonly repository: string;
  
  /**
   * The branch name to restrict access to
   */
  readonly branch?: string;

  /**
   * The environment name to restrict access to
   */
  readonly environment?: string;
}

export class DeploymentRole extends Construct {
  public readonly role: cdk.aws_iam.IRole;

  constructor(scope: Construct, id: string, props: DeploymentRoleProps) {
    super(scope, id);

    // Create conditions for branch and environment if specified
    const conditions: { [key: string]: any } = {
      StringLike: {
        'token.actions.githubusercontent.com:sub': `repo:${props.repository}:*`
      }
    };

    if (props.branch) {
      conditions.StringLike['token.actions.githubusercontent.com:sub'] = 
        `repo:${props.repository}:ref:refs/heads/${props.branch}`;
    }

    if (props.environment) {
      conditions.StringEquals = {
        'token.actions.githubusercontent.com:sub': `repo:${props.repository}:environment:${props.environment}`
      };
    }

    // Create the IAM role that can be assumed via web identity
    this.role = new cdk.aws_iam.Role(this, 'Role', {
      assumedBy: new cdk.aws_iam.WebIdentityPrincipal(
        'token.actions.githubusercontent.com',
        conditions
      ),
      description: `Deployment role for ${props.repository}`,
    });

    // Allow the role to assume the CDK deploy role

    // Export the role ARN as an output
    new cdk.CfnOutput(this, 'RoleArn', {
      value: this.role.roleArn,
    });
  }
}