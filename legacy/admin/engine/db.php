<?

$db =  mysqli_connect(DB_HOST, DB_LOGIN, DB_PASS, DB_NAME);
mysqli_set_charset($db , "utf8" );


if(mysqli_connect_error()){
    exit( 'error db connection' );
}
?>
