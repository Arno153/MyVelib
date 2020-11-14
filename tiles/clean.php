<?php
	
	array_map( 'unlink', array_filter((array) glob("./gp/*") ) );
	array_map( 'unlink', array_filter((array) glob("./gp2/*") ) );
	//array_map( 'unlink', array_filter((array) glob("./hot/*") ) );
	
	echo "Done";
	
?>