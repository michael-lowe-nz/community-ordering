import { Construct } from 'constructs';
import { aws_lambda_nodejs, aws_ec2 as ec2, aws_iam as iam, StackProps } from 'aws-cdk-lib';
import { FckNatInstanceProvider } from 'cdk-fck-nat';

interface HobbyVPCProps extends StackProps {
    createTestInstance: boolean;
    /**
     * Turn off at night
     * @default false 
     * Specifies whether the NAT instances, NAT gateways, and EC2 instances should be turned off at night.
     */
    turnOffAtNight: boolean;
}
export class HobbyVPC extends Construct {
    public readonly vpc: ec2.Vpc;
    public readonly natInstance: FckNatInstanceProvider;
    public readonly testInstance?: ec2.Instance;
    public readonly turnOffAtNight: boolean;

    constructor(scope: Construct, id: string, props: HobbyVPCProps) {
        super(scope, id);

        this.turnOffAtNight = props.turnOffAtNight;

        // Create NAT instance using cdk-fck-nat
        this.natInstance = new FckNatInstanceProvider({
            instanceType: ec2.InstanceType.of(ec2.InstanceClass.T4G, ec2.InstanceSize.NANO),
        });

        // Create the VPC with 1 AZ, 1 public subnet, and 1 private subnet
        this.vpc = new ec2.Vpc(this, 'VPC', {
            maxAzs: 2,
            ipAddresses: ec2.IpAddresses.cidr('10.0.0.0/26'),
            subnetConfiguration: [
                {
                    name: 'Public',
                    subnetType: ec2.SubnetType.PUBLIC,
                    cidrMask: 28,
                },
                {
                    name: 'Private',
                    subnetType: ec2.SubnetType.PRIVATE_WITH_EGRESS,
                    cidrMask: 28,
                },
            ],
            natGatewayProvider: this.natInstance,
        });

        this.natInstance.securityGroup.addIngressRule(ec2.Peer.ipv4(this.vpc.vpcCidrBlock), ec2.Port.allTraffic());

        // Create security group for EC2 Instance Connect Endpoint
        const eicSecurityGroup = new ec2.SecurityGroup(this, 'EC2InstanceConnectSecurityGroup', {
            vpc: this.vpc,
            description: 'Security group for EC2 Instance Connect Endpoint',
            allowAllOutbound: true,
        });

        if(props.createTestInstance) {
            // Create EC2 Instance Connect Endpoint
            new ec2.CfnInstanceConnectEndpoint(this, 'EC2InstanceConnectEndpoint', {
                subnetId: this.vpc.privateSubnets[0].subnetId,
                securityGroupIds: [eicSecurityGroup.securityGroupId],
            });
    
            // Create security group for test instance
            const testSecurityGroup = new ec2.SecurityGroup(this, 'TestSecurityGroup', {
                vpc: this.vpc,
                description: 'Security group for test instance',
                allowAllOutbound: true,
            });
    
            // Allow SSH access from the EC2 Instance Connect Endpoint using L2 construct
            testSecurityGroup.addIngressRule(
                ec2.SecurityGroup.fromSecurityGroupId(this, 'EICSecurityGroupReference', eicSecurityGroup.securityGroupId),
                ec2.Port.tcp(22),
                'Allow SSH from EC2 Instance Connect Endpoint'
            );
    
            // Create test instance with connectivity test script
            const testUserData = ec2.UserData.forLinux();
            testUserData.addCommands(
                'yum update -y',
                'yum install -y amazon-cloudwatch-agent curl',
    
                // Create a test script
                'cat << EOF > /home/ec2-user/test-connectivity.sh',
                '#!/bin/bash',
                'echo "Testing connectivity..."',
                'curl -s --max-time 5 http://checkip.amazonaws.com || echo "Failed to reach internet"',
                'echo "Testing DNS resolution..."',
                'nslookup amazon.com || echo "Failed to resolve DNS"',
                'EOF',
    
                'chmod +x /home/ec2-user/test-connectivity.sh',
    
                // Run the test immediately and save to a log file
                '/home/ec2-user/test-connectivity.sh > /home/ec2-user/connectivity-test.log 2>&1'
            );
    
            this.testInstance = new ec2.Instance(this, 'TestInstance', {
                vpc: this.vpc,
                vpcSubnets: {
                    subnetType: ec2.SubnetType.PRIVATE_WITH_EGRESS,
                },
                instanceType: ec2.InstanceType.of(ec2.InstanceClass.T3, ec2.InstanceSize.NANO),
                machineImage: new ec2.AmazonLinuxImage({
                    generation: ec2.AmazonLinuxGeneration.AMAZON_LINUX_2,
                    cpuType: ec2.AmazonLinuxCpuType.X86_64,
                }),
                securityGroup: testSecurityGroup,
                userData: testUserData,
                requireImdsv2: true,
                instanceName: `${this.node.path}-test-instance`,
            });
    
            /// Add the SSMManagedCore policy to the instance role
            this.testInstance.role.addManagedPolicy(
                iam.ManagedPolicy.fromAwsManagedPolicyName('EC2InstanceConnect')
            );
        }

        if (this.turnOffAtNight) {
            // Create a Lambda function to scale the NAT instance ASG down to 0 instances at night
            new aws_lambda_nodejs.NodejsFunction(this, 'ScaleDownNatInstance', {
                entry: 'src/lambda/scale-down-nat-instance.ts',
                handler: 'handler',
                environment: {
                    NAT_INSTANCE_ASG_NAMES: this.natInstance.autoScalingGroups.map((asg) => asg.autoScalingGroupName).join(','),
                },
            });
        }
        
    }
}