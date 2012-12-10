<?php

/**
 * Command class.

 * @author David Heward
 * @version 0.1

 * Usage:
 * Open terminal/shell application.
 * php cmd.php
 * Run commands "S","X","C","L","V","H","F","I"
 * See documentation for accepted parameters.
 *
 * To terminate session enter "X"
 */

class Command{

	static $accepted_cmds = array("S","X","C","L","V","H","F","I");
	private $image = array();
	private $errors = 0;
	private $fill_original_color;

	private $inputs;
	private $cmd;
	private $rows;
	private $cols;

	/**
	 * Initialise
	 */
	function __construct(){}

	/**
	 * S function - outputs the current array to the command line.
	 *
	 * @access private
	 * @return void
	 */
	function S(){
		if(is_array($this->image) && !empty($this->image))
		{
			$output = "=>\n";
			foreach($this->image as $image)
			{
				 foreach($image['cols'] as $k=>$v)
				 {
					 $output.=$v;
				 }
				 $this->cli_write($output);
				 $output = '';
			}

		}

	}

	/**
	 * I function creates an image E.G. (I 5 6)
	 *
	 * @access private
	 * @return void
	 */
	private function I(){

		if(count($this->inputs) == 2
		&& is_numeric($this->inputs[0])
		&& is_numeric($this->inputs[1])
		&& $this->__dimensions_validate())
		{
			/* Save the dimensions */
			$this->rows = $this->inputs[1];
			$this->cols = $this->inputs[0];

			/* Create the array */
			for($i = 1; $i <= $this->inputs[1]; $i++)
			{
				$columns = array();
				for($c = 1; $c <= $this->inputs[0]; $c++)
				{
					$columns[$c] = 'O';
				}
				$this->image[$i] = array("cols" => $columns);
			}

			// output to cli
			$this->cli_write("Image created successfully.");

		}
		else
		{
			throw new Exception("Invalid inputs.");
		}
	}

	/**
	 * C function - clears and resets all cells in the array
	 *
	 * @access private
	 * @return void
	 */
	private function C(){
		foreach($this->image as $row_key => $row)
		{
			foreach($row['cols'] as $column_key => $column)
			{
				$this->image[$row_key]['cols'][$column_key] = "O";
			}
		}
		$this->cli_write("Image reset.");
	}

	/**
	 * L function.
	 *
	 * @access private
	 * @return void
	 */
	private function L(){

		if(count($this->inputs) == 3)
		{
			$this->image[$this->inputs[1]]['cols'][$this->inputs[0]] = strtoupper($this->inputs[2]);
			$this->cli_write("Image modified.");
		}

	}


	/**
	 * V function.
	 *
	 * @access private
	 * @return void
	 */
	private function V(){
		if(count($this->inputs) == 4)
		{

			// make sure a reference is not being
			// made to rows or cols which don't exist
			if($this->__is_row_range(array(
				$this->inputs[1],
				$this->inputs[2]
			)))
			{

				if($this->__is_col_range($this->inputs[0]))
				{

					$rowcount = 1;
					foreach($this->image as $rk => $row)
					{
						if($rowcount >= $this->inputs[1] && $rowcount <= $this->inputs[2])
						{
							$this->image[$rk]['cols'][$this->inputs[0]] = strtoupper($this->inputs[3]);
						}
						$rowcount++;
					}

					// output
					$this->cli_write("Image modified.");

				}
				else
				{
					throw new Exception("Specified column is out of image range.");
				}
			}
			else
			{
				throw new Exception("One or more row value is out of image range.");
			}
		}
		else
		{
			throw new Exception("Too few arguments.");
		}
	}


	/**
	 * H function.
	 *
	 * @access private
	 * @return void
	 */
	private function H(){

		if(count($this->inputs) == 4)
		{

			if($this->__is_col_range(array(
				$this->inputs[0],
				$this->inputs[1]
			)))
			{

				if($this->__is_row_range($this->inputs[2]))
				{
					$loopcounter = 1;
					foreach($this->image[$this->inputs[2]]['cols'] as $ck => $column)
					{

						if($loopcounter >= $this->inputs[0] && $loopcounter <= $this->inputs[1])
						{
							$this->image[$this->inputs[2]]['cols'][$ck] = strtoupper($this->inputs[3]);
						}
						$loopcounter++;
					}

					// output
					$this->cli_write("Image modified.");

				}
				else
				{
					throw new Exception("Specified row is out of image range.");
				}

			}
			else
			{
				throw new Exception("Specified column range is invalid.");
			}

		}
		else
		{
			throw new Exception("Too few arguments.");
		}

	}


	/**
	 * F() function - fills a region of color.
	 * If any array pieces with hypothetically touching sides
	 * are surrounding it then they are also part of this region.
	 *
	 * @access private
	 * @return void
	 */
	private function F(){
		// set the original color
		$this->fill_original_color = $this->image[$this->inputs[0]]['cols'][$this->inputs[1]];

		// piggy back off the L function as to not duplicate fills
		$this->L();

		$outers = array(
			0 => array(
					$this->inputs[0],
					$this->inputs[1]
				)
		);

		// now deal with the regions
		while($outers) $outers = $this->__region($outers);

	}



	/**
	 * __action function - triggers and filters user actions
	 *	ensuring only valid actions are actioned passing inputs through to a valid cmd func
	 *
	 * @access public
	 * @param mixed $inputs
	 * @return void
	 */
	public function __action($inputs){

		// explode inputs by space
		$this->inputs = explode(" ", $inputs);
		// force upper
		$this->cmd = strtoupper(array_shift($this->inputs));

		// check validity of cmd
		if($this->cmd && $this->__is_cmd_valid())
		{
			// continue if method is implemented.
			if(method_exists($this,$this->cmd))
			{
				// valid action call.
				$this->{$this->cmd}();
			}
			else
			{
				throw new Exception("Method not implemented.");
			}
		}
		else
		{
			throw new Exception('Cmd received is invalid. Try again.');
		}
	}


	/**
	 * __region function.
	 * Fills a X,Y coord with a fill color
	 * Any hypothetically touching sides must also then be filled recursively.
	 *
	 * @access private
	 * @param mixed $coords
	 * @return void
	 */
	private function __region($coords){

		if(is_array($coords))
		{

			foreach($coords as $coord)
			{

				$row = $coord[0];
				$cell = $coord[1];

	    		if(isset($this->image[$row]["cols"][$cell]))
	    		{

	    			// traverse to the left and right of the choosen cell.
	    			$x=-1;

	    			for($cell_pos = $cell - 1; $cell_pos <= $cell + 1; $cell_pos++)
	    			{

		    			$y=-1;
		    			for($row_pos = $row - 1; $row_pos <= $row + 1; $row_pos++)
		    			{

			    			$corner = ($y == 0 || $x == 0) ? false : true;
			    			if(!$corner)
			    			{

                            	$success = $this->__compare_colour($row_pos,$cell_pos);

                            	if($success) $outers[] = array($row_pos, $cell_pos);

                            }

                            $y++;

			    		}

			    		$x++;
	    			}

	        	} else return false;

	        } // endforeach

        return (isset($outers)) ? $outers : false;

        }// end is array

	}


	/**
	 * compare_colour function.
	 *
	 * @access private
	 * @param mixed $row
	 * @param mixed $col
	 * @return void
	 */
	private function __compare_colour($row, $col){

	    if(isset($this->image[$row]["cols"][$col]))
	    {

	        if($this->fill_original_color == $this->image[$row]["cols"][$col])
	        {

	            $this->image[$row]["cols"][$col] = strtoupper($this->inputs[2]);

	            return true;
	        } else return false;
	    } else return false;
	}

	/**
	 * is_row_range function.
	 *
	 * @access private
	 * @param mixed $rows
	 * @return void
	 */
	private	function __is_row_range($rows){
		$range = range(1,$this->rows);
		if(!is_array($rows))
		{
			if(in_array($rows,$range))
				return true;
			return false;
		}
		else
		{
			foreach($rows as $row_value)
			{
				if(!in_array($row_value, $range))
				{
					$error++;
				}
			}

			if(!$error)
				return true;
			return false;
		}
	}


	/**
	 * is_col_range function.
	 *
	 * @access private
	 * @param mixed $cols
	 * @return void
	 */
	private function __is_col_range($cols){
		$range = range(1,$this->cols);
		if(!is_array($cols))
		{

			if(in_array($cols,$range))
				return true;
			return false;
		}
		else
		{
			foreach($cols as $col_value)
			{
				if(!in_array($col_value, $range))
				{
					$error++;
				}
			}

			if(!$error)
				return true;
			return false;
		}
	}

	/**
	 * is_cmd_valid function.
	 *
	 * @access private
	 * @return void
	 */
	private function __is_cmd_valid(){
		if(in_array($this->cmd, self::$accepted_cmds))
		{
			return true;
		}
		return false;
	}


	/**
	 * dimensions_validate function.
	 *
	 * @access private
	 * @return void
	 */
	private function __dimensions_validate(){
		$args = func_get_args();
		foreach($args as $value)
		{
			if(!is_numeric($value) || $value < 1 || $value > 250)
				$this->errors++;
		}
		if($this->errors == 0)
			return true;

		return false;
	}


	/**
	 * cli_write function.
	 *
	 * @access public
	 * @param mixed $message
	 * @return void
	 */
	public function cli_write($message){
		if($message)
			fwrite(STDOUT,$message."\n");
	}

}
?>