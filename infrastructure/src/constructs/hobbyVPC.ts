import { Construct } from 'constructs';
import * as ec2 from 'aws-cdk-lib/aws-ec2';

export class SingleAzVpcWithNat extends Construct {
    public readonly vpc: ec2.Vpc;
    public readonly natInstance: ec2.Instance;

    constructor(scope: Construct, id: string) {
        super(scope, id);

        // Create the VPC with 1 AZ, 1 public subnet, and 1 private subnet
        this.vpc = new ec2.Vpc(this, 'VPC', {
            maxAzs: 1,
            subnetConfiguration: [
                {
                    name: 'Public',
                    subnetType: ec2.SubnetType.PUBLIC,
                    cidrMask: 24,
                },
                {
                    name: 'Private',
                    subnetType: ec2.SubnetType.PRIVATE_WITH_EGRESS,
                    cidrMask: 24,
                },
            ],
            natGateways: 0, // We don't want a NAT Gateway since we're using a NAT Instance
        });

        // Create security group for NAT instance
        const natSecurityGroup = new ec2.SecurityGroup(this, 'NatSecurityGroup', {
            vpc: this.vpc,
            description: 'Security group for NAT Instance',
            allowAllOutbound: true,
        });

        // Allow inbound HTTP/HTTPS traffic from private subnet
        natSecurityGroup.addIngressRule(
            ec2.Peer.ipv4(this.vpc.privateSubnets[0].ipv4CidrBlock),
            ec2.Port.tcp(80),
            'Allow HTTP from private subnet'
        );
        natSecurityGroup.addIngressRule(
            ec2.Peer.ipv4(this.vpc.privateSubnets[0].ipv4CidrBlock),
            ec2.Port.tcp(443),
            'Allow HTTPS from private subnet'
        );

        // Create NAT instance
        this.natInstance = new ec2.Instance(this, 'NatInstance', {
            vpc: this.vpc,
            vpcSubnets: {
                subnetType: ec2.SubnetType.PUBLIC,
            },
            instanceType: ec2.InstanceType.of(ec2.InstanceClass.T3, ec2.InstanceSize.NANO),
            machineImage: new ec2.AmazonLinuxImage({
                generation: ec2.AmazonLinuxGeneration.AMAZON_LINUX_2,
                cpuType: ec2.AmazonLinuxCpuType.X86_64,
            }),
            securityGroup: natSecurityGroup,
            sourceDestCheck: false, // Required for NAT instance
        });

        // Add route from private subnet to NAT instance
        const privateRouteTable = this.vpc.privateSubnets[0].routeTable;

        new ec2.CfnRoute(this, 'NatRoute', {
            routeTableId: privateRouteTable.routeTableId,
            destinationCidrBlock: '0.0.0.0/0',
            instanceId: this.natInstance.instanceId,
        });
    }
}
