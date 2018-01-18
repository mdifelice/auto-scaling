<?php
/**
 * AWS handler.
 *
 * @package AWS_Tools
 */

define( 'AWS_DEFAULT_REGION', 'us-west-2' );
define( 'AWS_DEFAULT_ZONE',   'us-west-2a' );

/**
 * Include Amazon library.
 */
require_once __DIR__ . '/amazon/aws-autoloader.php';

/**
 * Prepares the AWS objects to be used.
 *
 * @param string $region Optional. Specify which region to use. Default is
 *                       the constant AWS_DEFAULT_REGION.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 * @global Aws\Rds\RdsClient $aws_rds_client
 * @global Aws\ElasticLoadBalancing\ElasticLoadBalancingClient $aws_elb_client
 * @global Aws\S3\S3Client $aws_s3_client
 * @global Aws\CloudWatch\CloudWatchClient $aws_cw_client
 */ 
function aws_init( $region = AWS_DEFAULT_REGION ) {
	global $aws_as_client, $aws_ec2_client, $aws_rds_client, $aws_elb_client, $aws_s3_client, $aws_cw_client;

	$default_parameters = array(
		'version' => 'latest',
		'region'  => $region,
	);

	$aws_as_client	= Aws\AutoScaling\AutoScalingClient::factory( $default_parameters );
	$aws_ec2_client	= Aws\Ec2\Ec2Client::factory( $default_parameters );
	$aws_rds_client	= Aws\Rds\RdsClient::factory( $default_parameters );
	$aws_elb_client	= Aws\ElasticLoadBalancing\ElasticLoadBalancingClient::factory( $default_parameters );
	$aws_s3_client	= Aws\S3\S3Client::factory( $default_parameters );
	$aws_cw_client	= Aws\CloudWatch\CloudWatchClient::factory( $default_parameters );
}

/**
 * Calls the AWS API in order to fetch an auto scaling group and returns
 * it.
 *
 * @param string $auto_scaling_group_name Name of the auto scaling group.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 *
 * @return array The auto scaling group or NULL if not found.
 */
function get_auto_scaling_group( $auto_scaling_group_name ) {
	global $aws_as_client;

	$auto_scaling_group = null;

	$result = $aws_as_client->describeAutoScalingGroups( array(
		'AutoScalingGroupNames' => array( $auto_scaling_group_name ),
	) );

	$auto_scaling_groups = $result->get( 'AutoScalingGroups' );

	if ( $auto_scaling_groups ) {
		foreach ( $auto_scaling_groups as $possible_auto_scaling_group ) {
			if ( $possible_auto_scaling_group['AutoScalingGroupName'] === $auto_scaling_group_name ) {
				$auto_scaling_group = $possible_auto_scaling_group;

				break;
			}
		}
	}

	return $auto_scaling_group;
}

/**
 * Sets the desired capacity for an auto scaling group.
 *
 * @param string $auto_scaling_group_name Name of the auto scaling group.
 * @param int    $desired_capacity The desired capacity.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 */
function set_desired_capacity( $auto_scaling_group_name, $desired_capacity ) {
	global $aws_as_client;

	$aws_as_client->setDesiredCapacity( array(
		'AutoScalingGroupName'	=> $auto_scaling_group_name,
		'DesiredCapacity'		=> $desired_capacity,
		'HonorCooldown'			=> false,
	) );
}

/**
 * Calls the AWS API in order to fetch a launch configuration.
 *
 * @param string $launch_configuration_name Name of the launch
 * configuration.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 *
 * @return array The launch configuration or NULL if not found.
 */
function get_launch_configuration( $launch_configuration_name ) {
	global $aws_as_client;

	$launch_configuration = null;

	$result = $aws_as_client->describeLaunchConfigurations( array(
		'LaunchConfigurationNames' => array( $launch_configuration_name ),
	) );

	$launch_configurations = $result->get( 'LaunchConfigurations' );

	if ( $launch_configurations ) {
		foreach ( $launch_configurations as $possible_launch_configuration ) {
			if ( $possible_launch_configuration['LaunchConfigurationName'] === $launch_configuration_name ) {
				$launch_configuration = $possible_launch_configuration;

				break;
			}
		}
	}

	return $launch_configuration;
}

/**
 * Creates a launch configuration based on another launch configuration.
 *
 * @param string $name Name of the launch configuration.
 * @param string $image_id ID of the image for the launch configuration.
 * @param string $base_launch_configuration The base launch configuration.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 */
function create_launch_configuration( $name, $image_id, $base_launch_configuration ) {
	global $aws_as_client;

	$aws_as_client->createLaunchConfiguration( array(
		'LaunchConfigurationName'	=> $name,
		'ImageId'					=> $image_id,
		'InstanceType'				=> $base_launch_configuration['InstanceType'],
		'SecurityGroups'			=> $base_launch_configuration['SecurityGroups'],
	) );
}

/**
 * Associates a launch configuration with an auto scaling group.
 *
 * @param string $launch_configuration_name Name of the launch
 * configuration.
 * @param string $auto_scaling_group_name Name of the auto scaling group.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 */
function set_launch_configuration( $launch_configuration_name, $auto_scaling_group_name ) {
	global $aws_as_client;

	$aws_as_client->updateAutoScalingGroup( array(
		'LaunchConfigurationName'	=> $launch_configuration_name,
		'AutoScalingGroupName'		=> $auto_scaling_group_name,
	) );
}

/**
 * Deletes a launch configuration.
 *
 * @param string $launch_configuration_name Name of the launch
 * configuration.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 */
function delete_launch_configuration( $launch_configuration_name ) {
	global $aws_as_client;

	$aws_as_client->deleteLaunchConfiguration( array(
		'LaunchConfigurationName'	=> $launch_configuration_name,
	) );
}

/**
 * Terminates an instance in an auto scaling group.
 *
 * @param string  $instance_id The ID of the instance to terminate.
 * @param boolean $should_decrement_desired_capacity If the auto scaling system
 * should adjust its desired capacity.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 */
function terminate_instance_in_auto_scaling_group( $instance_id, $should_decrement_desired_capacity ) {
	global $aws_as_client;

	$aws_as_client->terminateInstanceInAutoScalingGroup( array(
		'InstanceId'						=> $instance_id,
		'ShouldDecrementDesiredCapacity'	=> $should_decrement_desired_capacity,
	) );
}

/**
 * Calls the AWS API in order to fetch an image.
 *
 * @param string $image_id ID of the image to fetch.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 *
 * @return array The image or NULL if not found.
 */
function get_image( $image_id ) {
	global $aws_ec2_client;

	$image = null;

	$result = $aws_ec2_client->describeImages( array(
		'ImageIds' => array( $image_id ),
	) );

	$images = $result->get( 'Images' );

	if ( $images ) {
		foreach ( $images as $possible_image ) {
			if ( $possible_image['ImageId'] === $image_id ) {
				$image = $possible_image;

				break;
			}
		}
	}

	return $image;
}

/**
 * Creates an EC2 instance.
 *
 * @param string $image_id        Image ID for the new instance.
 * @param string $subnet_id       ID of the subnet where the instance will be
 *                                created.
 * @param array  $security_groups List of security group IDs that will be
 *                                assigned to the instance.
 * @param string $key_pair_name   Optional. Which key pair to use.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 *
 * @return array The instance recently created or NULL if it could not be
 * created.
 */
function run_instance( $image_id, $subnet_id, $security_groups, $key_pair_name = null ) {
	global $aws_ec2_client;

	$instance = null;

	$parameters = array(
		'ImageId'		=> $image_id,
		'MinCount'		=> 1,
		'MaxCount'		=> 1,
		'InstanceType'	=> 'm3.medium',
	);

	if ( ! empty( $subnet_id ) ) {
		$parameters['SubnetId'] = $subnet_id;
	}

	if ( ! empty( $security_groups ) ) {
		$parameters['SecurityGroupIds'] = $security_groups;
	}

	if ( ! empty( $key_pair_name ) ) {
		$parameters['KeyName'] = $key_pair_name;
	}

	$result = $aws_ec2_client->runInstances( $parameters );

	$instances = $result->get( 'Instances' );

	if ( ! empty( $instances ) ) {
		$instance = $instances[0];
	}

	if ( ! $instance ) {
		throw new Exception( 'Could not create temporal instance.' );
	}

	return $instance;
}

/**
 * Gets info about several instances.
 *
 * @param array $instances_ids An array of instances IDs.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 *
 * @return array An array of instances.
 */
function get_instances( $instances_ids ) {
	global $aws_ec2_client;

	$result = $aws_ec2_client->describeInstances( array(
		'InstanceIds'	=> $instances_ids,
	) );

	$reservations = $result->get( 'Reservations' );
	$instances    = array();

	foreach ( $reservations as $reservation ) {
		$instances = array_merge( $instances, $reservation['Instances'] );
	}

	return $instances;
}

/**
 * Gets info about an instance.
 *
 * @param string $instance_id Instance Id.
 *
 * @return array Instance information.
 */
function get_instance( $instance_id ) {
	$instances = get_instances( array( $instance_id ) );
	$instance  = null;

	if ( ! empty( $instances ) ) {
		$instance = $instances[0];
	}

	return $instance;
}

/**
 * Returns an instance status.
 *
 * @param string $instance_id The instance ID.
 *
 * @return string The status of the instance.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 */
function get_instance_status( $instance_id ) {
	global $aws_ec2_client;

	$result = $aws_ec2_client->describeInstanceStatus( array(
		'InstanceIds' => array( $instance_id ),
	) );

	$instance_statuses = $result->get( 'InstanceStatuses' );

	$status = null;

	foreach ( $instance_statuses as $instance_status ) {
		if ( $instance_status['InstanceId'] === $instance_id ) {
			$status = $instance_status['InstanceStatus']['Status'];

			break;
		}
	}

	return $status;
}

/**
 * Checks if an instance is in an specific status or state. If not, it waits
 * until it is.
 *
 * @param string $instance_id The instance ID to check for.
 * @param array  $check An associative array indicating what to check for.
 */
function wait_instance( $instance_id, $check ) {
	$total_checks = count( $check );

	do {
		sleep( 1 );

		$passed_checks = 0;

		if ( isset( $check['state'] ) ) {
			$instances = get_instances( array( $instance_id ) );

			$state = null;

			if ( ! empty( $instances ) ) {
				$instance = $instances[0];

				$state = $instance['State']['Name'];
			}

			if ( $state === $check['state'] ) {
				$passed_checks++;
			}
		}

		if ( isset( $check['status'] ) ) {
			if ( get_instance_status( $instance_id ) === $check['status'] ) {
				$passed_checks++;
			}
		}
	} while ( $passed_checks < $total_checks );
}

/**
 * Checks if an image is in an specific state. If not, it waits until it is.
 *
 * @param string $image_id The image ID to check for.
 * @param string $state_to_check Optional. State to check. By default is
 * 'available'.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 */
function wait_image( $image_id, $state_to_check = 'available' ) {
	global $aws_ec2_client;

	do {
		sleep( 1 );

		$image = get_image( $image_id );

		$state = null;

		if ( null !== $image ) {
			$state = $image['State'];
		}
	} while ( $state !== $state_to_check );
}

/**
 * Create tags for an specific resource.
 *
 * @param string $resource_id The resource ID (can be an instance, an
 * image, etc.).
 * @param array  $tags Array of tags, using the key as tag name and the
 * value as tag value.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 */
function create_tags( $resource_id, $tags ) {
	global $aws_ec2_client;

	$parsed_tags = array();

	foreach ( $tags as $key => $value ) {
		$parsed_tags[] = array(
			'Key'	=> $key,
			'Value'	=> $value,
		);
	}

	$aws_ec2_client->createTags( array(
		'Resources'	=> array( $resource_id ),
		'Tags'		=> $parsed_tags,
	) );
}

/**
 * Stops an EC2 instance.
 *
 * @param string $instance_id ID of the instance to stop.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 */
function stop_instance( $instance_id ) {
	global $aws_ec2_client;

	$aws_ec2_client->stopInstances( array(
		'InstanceIds' => array( $instance_id ),
	) );
}

/**
 * Terminates an EC2 instance.
 *
 * @param string $instance_id ID of the instance to terminate.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 */
function terminate_instance( $instance_id ) {
	global $aws_ec2_client;

	$aws_ec2_client->terminateInstances( array(
		'InstanceIds' => array( $instance_id ),
	) );
}

/**
 * Creates a new image based on an instance.
 *
 * @param string $name Name of the image.
 * @param string $instance_id ID of the instance.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 *
 * @return string The ID of the recently created image.
 */
function create_image( $name, $instance_id ) {
	global $aws_ec2_client;

	$result = $aws_ec2_client->createImage( array(
		'Name'			=> $name,
		'InstanceId'	=> $instance_id,
	) );

	$image_id = $result->get( 'ImageId' );

	if ( ! $image_id ) {
		throw new Exception( 'Cannot create image.' );
	}

	return $image_id;
}

/**
 * Deregisters an image.
 *
 * @param string $image_id ID of the image.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 */
function deregister_image( $image_id ) {
	global $aws_ec2_client;

	$aws_ec2_client->deregisterImage( array(
		'ImageId' => $image_id,
	) );
}

/**
 * Deletes a snapshot.
 *
 * @param string $snapshot_id ID of the snapshot.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 */
function delete_snapshot( $snapshot_id ) {
	global $aws_ec2_client;

	$aws_ec2_client->deleteSnapshot( array(
		'SnapshotId' => $snapshot_id,
	) );
}

/**
 * Creates a security group.
 *
 * @param string $name        Name of the security group.
 * @param string $description Description of the security group.
 * @param array  $rules       Set of ingress rules.
 *
 * @global Aws\Ec2\Ec2Client $aws_ec2_client
 */
function create_security_group( $name, $description, $rules ) {
	global $aws_ec2_client;

	$aws_ec2_client->createSecurityGroup( array(
		'GroupName'   => $name,
		'Description' => $description,
	) );

	foreach ( $rules as $rule ) {
		$rule['GroupName'] = $name;

		$aws_ec2_client->authorizeSecurityGroupIngress( $rule );
	}
}

/**
 * Suspends auto scaling processes.
 *
 * @param string $auto_scaling_group_name Name of the auto scaling group.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 */
function suspend_auto_scaling_processes( $auto_scaling_group_name ) {
	global $aws_as_client;

	$scaling_processes = get_auto_scaling_processes();

	$aws_as_client->suspendProcesses( array(
		'AutoScalingGroupName' => $auto_scaling_group_name,
		'ScalingProcesses'     => $scaling_processes,
	) );
}

/**
 * Resumes auto scaling processes.
 *
 * @param string $auto_scaling_group_name Name of the auto scaling group.
 *
 * @global Aws\AutoScaling\AutoScalingClient $aws_as_client
 */
function resume_auto_scaling_processes( $auto_scaling_group_name ) {
	global $aws_as_client;

	$scaling_processes = get_auto_scaling_processes();

	$aws_as_client->resumeProcesses( array(
		'AutoScalingGroupName' => $auto_scaling_group_name,
		'ScalingProcesses'     => $scaling_processes,
	) );
}

/**
 * Returns current instance metadata.
 *
 * @param string $metadata Which data to retrieve.
 *
 * @return array Each one of the lines of the response.
 */
function get_instance_metadata( $metadata ) {
	$response = null;
	$file     = sprintf( 'http://169.254.169.254/latest/meta-data/%s', $metadata );
	$contents = file_get_contents( $file );

	if ( false !== $contents ) {
		$response = array_map( 'urldecode', explode( "\n", $contents ) );
	}

	return $response;
}

/**
 * The functions below here need documentation.
 */
function create_rds_security_group( $name, $description, $rules ) {
	global $aws_rds_client;

	$response = $aws_rds_client->createDBSecurityGroup( array(
		'DBSecurityGroupName'        => $name,
		'DBSecurityGroupDescription' => $description,
	) );

	$security_group = $response->get( 'DBSecurityGroup' );

	foreach ( $rules as $rule ) {
		$rule['DBSecurityGroupName']     = $name;
		$rule['EC2SecurityGroupOwnerId'] = $security_group['OwnerId'];

		$aws_rds_client->authorizeDBSecurityGroupIngress( $rule );
	}
}

function create_snapshot( $volume_id, $description, $tags ) {
	global $aws_ec2_client;

	$response = $aws_ec2_client->createSnapshot( array(
		'VolumeId'    => $volume_id,
		'Description' => $description,
	) );

	if ( 'error' === $response['State'] ) {
		throw new Exception( sprintf( 'Cannot create snapshot for volume %s.', $volume_id ) );
	}

	$snapshot_id = $response['SnapshotId'];

	if ( ! empty( $tags ) ) {
		$args = array();

		foreach ( $tags as $key => $value ) {
			$args[] = array(
				'Key'   => $key,
				'Value' => $value,
				);
		}

		$aws_ec2_client->createTags( array(
			'Resources' => array( $snapshot_id ),
			'Tags'      => $args
		) );
	}
}

function get_snapshots( $volume_id ) {
	global $aws_ec2_client;

	$response = $aws_ec2_client->describeSnapshots( array(
		'Filters' => array(
			array(
				'Name' 	 => 'volume-id',
				'Values' => array( $volume_id ),
				),
			),
		)
	);

	return $response->get( 'Snapshots' );
}

function create_rds_instance( $engine, $name, $storage, $type, $user, $password, $security_groups ) {
	global $aws_rds_client;

	$instance = null;

	$parameters = array(
		'AllocatedStorage'     => $storage,
		'DBInstanceClass'	   => $type,
		'DBInstanceIdentifier' => $name,
		'DBName'               => $name,
		'DBSecurityGroups'     => $security_groups,
		'Engine'               => $engine,
		'MasterUsername'       => $user,
		'MasterUserPassword'   => $password,
	);

	$response = $aws_rds_client->createDBInstance( $parameters );

	return $response->get( 'DBInstance' );
}

function create_autoscaling_group( $name, $min_size, $max_size, $launch_configuration, $load_balancer, $policies = array(), $zone = AWS_DEFAULT_ZONE ) {
	global $aws_as_client, $aws_cw_client;

	$aws_as_client->createAutoScalingGroup( array(
		'AvailabilityZones'       => array( $zone ),
		'AutoScalingGroupName'    => $name,
		'HealthCheckType'         => 'ELB',
		'HealthCheckGracePeriod'  => 300,
		'LaunchConfigurationName' => $launch_configuration,
		'LoadBalancerNames'       => array( $load_balancer ),
		'MaxSize'                 => $max_size,
		'MinSize'                 => $min_size,
	) );

	foreach ( $policies as $policy_name => $policy ) {
		$response = $aws_as_client->putScalingPolicy( array(
			'AdjustmentType'              => 'ChangeInCapacity',
			'AutoScalingGroupName'        => $name,
			'PolicyName'                  => $policy_name,
			'ScalingAdjustment'           => $policy['Adjustment'],
		) );

		$policy_arn = $response->get( 'PolicyARN' );

		$aws_cw_client->putMetricAlarm( array(
			'AlarmActions'		 => array( $policy_arn ),
			'AlarmName'          => $policy_name,
			'ComparisonOperator' => $policy['Comparison'],
			'Dimensions'		 => array(
				array(
					'Name'  => 'AutoScalingGroupName',
					'Value' => $name,
				),
			),
			'EvaluationPeriods'  => 2,
			'MetricName'		 => $policy['Metric'],
			'Namespace'			 => 'AWS/EC2',
			'Period'			 => 120,
			'Statistic'			 => 'Average',
			'Threshold'			 => $policy['Threshold'],
		) );
	}
}

function create_http_load_balancer( $name, $zone = AWS_DEFAULT_ZONE ) {
	global $aws_elb_client, $aws_s3_client;

	/**
	 * First, we create the bucket for logging. If it fails, we do not continue. 
	 */
	$bucket = uniqid( $name );
		
	$aws_s3_client->createBucket( array(
		'Bucket' => $bucket,
	) );

	$response = $aws_elb_client->createLoadBalancer( array(
		'Listeners'         => array(
			array(
				'InstancePort'     => 80,
				'InstanceProtocol' => 'HTTP',
				'LoadBalancerPort' => 80,
				'Protocol'         => 'HTTP',
			),
		),
		'LoadBalancerName'  => $name,
		'AvailabilityZones' => array( $zone ),
	) );	

	$aws_elb_client->createLBCookieStickinessPolicy( array(
		'CookieExpirationPeriod' => 3600,
		'LoadBalancerName'       => $name,
		'PolicyName' 			 => $name,
	) );

	$aws_s3_client->putBucketPolicy( array(
		'Bucket' => $bucket,
		'Policy' => json_encode( array(
			'Id'        => $bucket,
			'Version'   => '2012-10-17',
			'Statement' => array(
				array(
					'Sid'      => sprintf( 'Stmt%s', uniqid() ),
					'Action'   => array(
						's3:PutObject',
					),
					'Effect'    => 'Allow',
					'Resource'  => sprintf( 'arn:aws:s3:::%s/AWSLogs/*', $bucket ),
					'Principal' => array(
						'AWS' => array(
							get_load_balancer_account_id( substr( $zone, 0, strlen( $zone ) - 1 ) ),
						),
					),
				),
			),
		) ),
	) );

	$aws_elb_client->modifyLoadBalancerAttributes( array(
		'LoadBalancerName'       => $name,
		'LoadBalancerAttributes' => array(
			'AccessLog' => array(
				'Enabled'      => true,
				'S3BucketName' => $bucket,
			)
		)
	) );

	return $response;
}

function delete_security_group( $name ) {
	global $aws_ec2_client;

	$aws_ec2_client->deleteSecurityGroup( array(
		'GroupName' => $name,
	) );
}

function delete_rds_security_group( $name ) {
	global $aws_rds_client;

	$aws_rds_client->deleteDBSecurityGroup( array(
		'DBSecurityGroupName' => $name,
	) );
}

function get_rds_instance( $name ) {
	global $aws_rds_client;

	$instance = null;
	$response = $aws_rds_client->describeDBInstances( array(
		'Filters' => array(
			array(
				'Name'   => 'db-instance-id',
				'Values' => array( $name ),
			),
		),
	) );

	$instances = $response->get( 'DBInstances' );

	if ( ! empty( $instances ) ) {
		$instance = $instances[0];
	}

	return $instance;
}

function delete_rds_instance( $name ) {
	global $aws_rds_client;

	$aws_rds_client->deleteDBInstance( array(
		'DBInstanceIdentifier' => $name,
		'SkipFinalSnapshot'    => true,
	) );
}

function create_key_pair( $name ) {
	global $aws_ec2_client;

	$response = $aws_ec2_client->createKeyPair( array(
		'KeyName' => $name,
	) );

	return $response['KeyMaterial'];
}

function delete_key_pair( $name ) {
	global $aws_ec2_client;

	$response = $aws_ec2_client->deleteKeyPair( array(
		'KeyName' => $name,
	) );
}

function get_load_balancer( $name ) {
	global $aws_elb_client;

	$response = $aws_elb_client->describeLoadBalancers( array(
		'LoadBalancerName' => $name,
	) );

	$load_balancers = $response->get( 'LoadBalancerDescriptions' );
	$load_balancer = null;

	foreach ( $load_balancers as $possible_load_balancer ) {
		if ( $possible_load_balancer['LoadBalancerName'] === $name ) {
			$load_balancer = $possible_load_balancer;

			break;
		}
	}

	return $load_balancer;
}

function describe_load_balancer_attributes( $name ) {
	global $aws_elb_client;

	$response = $aws_elb_client->describeLoadBalancerAttributes( array(
		'LoadBalancerName' => $name,
	) );

	return $response->get( 'LoadBalancerAttributes' );
}

function delete_load_balancer( $name ) {
	global $aws_elb_client;

	$aws_elb_client->deleteLoadBalancer( array(
		'LoadBalancerName' => $name,
	) );
}

function get_load_balancer_account_id( $region ) {
	$account_id = null;

	switch ( $region ) {
		case 'us-east-1':
			$account_id = '127311923021';
			break;
		case 'us-east-2':
			$account_id = '033677994240';
			break;
		case 'us-west-1':
			$account_id = '027434742980';
			break;
		case 'us-west-2':
			$account_id = '797873946194';
			break;
		case 'ca-central-1':
			$account_id = '985666609251';
			break;
		case 'eu-west-1':
			$account_id = '156460612806';
			break;
		case 'eu-central-1':
			$account_id = '054676820928';
			break;
		case 'eu-west-2':
			$account_id = '652711504416';
			break;
		case 'ap-northeast-1':
			$account_id = '582318560864';
			break;
		case 'ap-northeast-2':
			$account_id = '600734575887';
			break;
		case 'ap-southeast-1':
			$account_id = '114774131450';
			break;
		case 'ap-southeast-2':
			$account_id = '783225319266';
			break;
		case 'ap-south-1':
			$account_id = '718504428378';
			break;
		case 'sa-east-1':
			$account_id = '507241528517';
			break;
		case 'us-gov-west-1':
			$account_id = '048591011584';
			break;
		case 'cn-north-1':
			$account_id = '638102146993';
			break;
	}

	return $account_id;
}

function delete_bucket( $bucket ) {
	global $aws_s3_client;

	$response = $aws_s3_client->listObjects( array(
		'Bucket' => $bucket,
	) );

	$objects = $response->get( 'Contents' );

	foreach ( $objects as $object ) {
		$aws_s3_client->deleteObject( array(
			'Bucket' => $bucket,
			'Key'    => $object['Key'],
		 ) );
	}

	$aws_s3_client->deleteBucket( array(
		'Bucket' => $bucket,
	) );
}

function delete_autoscaling_group( $name ) {
	global $aws_as_client;

	$aws_as_client->deleteAutoScalingGroup( array(
		'AutoScalingGroupName' => $name,
		'ForceDelete'          => true,
	) );
}

function get_image_by_name( $name ) {
	global $aws_ec2_client;

	$image = null;

	$response = $aws_ec2_client->describeImages( array(
		'Filters' => array(
			array(
				'Name' 	 => 'name',
				'Values' => array( $name ),
			),
		),
		'Owners' => array(
			'self',
		),
	) );

	$images = $response->get( 'Images' );

	foreach ( $images as $possible_image ) {
		if ( $possible_image['Name'] === $name ) {
			$image = $possible_image;

			break;
		}
	}

	return $image;
}

function delete_alarms( $alarms ) {
	global $aws_cw_client;

	$aws_cw_client->deleteAlarms( array(
		'AlarmNames' => $alarms,
	) );
}
