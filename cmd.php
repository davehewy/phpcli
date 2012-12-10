<?php

/* load cmd.class.php */
require_once("./cmd.class.php");

/* create a new instance of Command */
$cmdLine = new Command;

/* output a welcome prompt to terminal */
$cmdLine->cli_write("Enter commands (enter the letter and press return)");

do{ // while selection != X

	do { $input = trim(strtolower(fgets(STDIN))); }

	while ( trim($input) == '' );

	/* Fire off the class function */
	try{

		$cmdLine->__action($input);

	} catch (Exception $e){

		$cmdLine->cli_write($e->getMessage());

	}

} while( $input != 'x'); $cmdLine->cli_write("Terminated.\n"); exit(0); //exit correctly

?>