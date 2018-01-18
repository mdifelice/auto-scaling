<?php
/**
 * Generic functions for scripts.
 *
 * @package AWS_Tools
 */

/**
 * Prints an error message.
 *
 * @param string $message Message to display.
 */
function error( $message ) {
	$output = sprintf( "%s\n", $message );

	error_log( $output, 4 );
}

/**
 * Prints a debug message.
 *
 * @param string $message Message to display.
 * @param bool 	 $pending Optional. If true, it will not print a new line,
 *						  and the next message it will printed in the same
 *						  line. By default is false.
 */
function message( $message, $pending = false ) {
	static $previous_pending = false;

	if ( ! $previous_pending ) {
		$message = sprintf( '[%s]: %s',
			date( 'Y-m-d H:i:s' ),
			$message
		);
	}

	$previous_pending = $pending;

	echo $message . ( $pending ? '' : PHP_EOL );
}

/**
 * It waits until an user enters a key.
 */
function wait_for_user_input() {
	$fp = fopen( 'php://stdin', 'r' );

	if ( $fp ) {
		/**
		 * The program will stuck here until a user press a key.
		 */
		fgets( $fp );

		fclose( $fp );
	}
}

/**
 * Check if a process is running.
 *
 * @param string $process_name An unique name to identify the process. It
 * will be used to generate a file name.
 *
 * @throws Exception When it cannot determine if the process is running.
 *
 * @return bool If the process is running.
 */
function check_if_process_is_running( $process_name ) {
	$process_file 		= sys_get_temp_dir() . '/' . basename( $process_name ) . '.pid';
	$process_is_running = false;
	$process_id			= null;

	if ( file_exists( $process_file ) ) {
		$process_id = file_get_contents( $process_file );

		if ( false === $process_id ) {
			throw new Exception( sprintf( 'Cannot read process file (%s)', $process_file ) );
		}
	}

	if ( null !== $process_id ) {
		$process_is_running = file_exists( sprintf( '/proc/%s', $process_id ) );
	}

	if ( false === $process_is_running ) {
		if ( false === file_put_contents( $process_file, getmypid() ) ) {
			throw new Exception( sprintf( 'Cannot write process file (%s)', $process_file ) );
		}

		register_shutdown_function( function() use ( $process_file ) {
			unlink( $process_file );
		} );
	}

	return $process_is_running;
}

/**
 * Deletes a files and folder recursively.
 *
 * This is a safe function. It only works inside the /tmp folder.
 *
 * @param string $file The file or folder to delete.
 *
 * @return bool If it could delete all the files.
 */
function delete_temporal_file( $file ) {
	$success = false;

	if ( preg_match( '/^\/tmp\/', $file ) ) {
		$success = true;

		if ( is_dir( $file ) ) {
			$fp = opendir( $file );

			while ( $file = readdir( $fp ) ) {
				if ( '.' !== $file && '..' !== $file ) {
					if ( ! delete_file_recursively( $file ) ) {
						$success = false;

						break;
					}
				}
			}
		} else {
			if ( ! is_writable( $file ) || ! unlink( $file ) ) {
				$success = false;
			}
		}
	}

	return $success;
}

/**
 * Syncs contents between the local instance and a remote one.
 *
 * @param array  $files_mapping Array of files to sync, using the key as
 * the source file, and the value as the remote file.
 * @param string  $key_pair_path Key par location.
 * @param string $instance_address IP or domain address of the remote
 * instance.
 *
 * @return boolean TRUE if everything worked correctly.
 */
function sync_content( $files_mapping, $key_pair_path, $instance_address ) {
	$error = false;

	foreach ( $files_mapping as $local_file => $remote_file ) {
		message( sprintf( 'Synchronizing %s...', $local_file ) );

		system( sprintf( "rsync -e 'ssh -o StrictHostKeyChecking=no -i %s' --rsync-path='sudo rsync' --exclude '.svn' --exclude '.git' -a --delete %s %s",
			escapeshellarg( $key_pair_path ),
			escapeshellarg( $local_file ),
			escapeshellarg( sprintf( 'ubuntu@%s:%s',
				$instance_address,
				$remote_file
			) )
		), $error );

		if ( $error ) {
			break;
		}
	}

	return ! $error;
}

/**
 * The functions below here need documentation.
 */
function execute_remote_command( $address, $key_pair_path, $command ) {
	system( sprintf( 'ssh -o StrictHostKeyChecking=no -i %s ubuntu@%s -t %s',
		escapeshellarg( $key_pair_path ),
		escapeshellarg( $address ),
		escapeshellarg( $command )
	), $error );

	return ! $error;
}
