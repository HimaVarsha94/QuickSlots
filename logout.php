<?php
/**
 * Destroys the session to logout the user and redirect to Homepage
 * @author Avin E.M; Kunal Dahiya
 */
session_start();
session_destroy();
header("Location: ./");    
?>
