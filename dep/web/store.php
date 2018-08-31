<?php
/*
Dromaeo Test Suite
Copyright (c) 2010 John Resig

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/

$server = 'mysql.dromaeo.com';
$user = 'dromaeo';
$pass = 'dromaeo';

require('JSON.php');

$json = new Services_JSON();
$sql = mysqli_connect( $server, $user, $pass );
if (!$sql) {
	http_response_code(503);
	die("Failed to connect to database server.");
}

if (!mysqli_select_db($sql, 'dromaeo')) {
	http_response_code(503);
	die("Failed to select database.");
}


if ( isset($_REQUEST['id']) ) {
	$id = preg_replace('/[^\d,]/', '', $_REQUEST['id']);
	$sets = array();
	$ids = explode(",", $id);

	foreach ($ids as $i) {
		$query = mysqli_query( $sql, sprintf("SELECT * FROM runs WHERE id=%s;",
			mysqli_real_escape_string($sql, $i)));
		$data = mysqli_fetch_assoc($query);

		$query = mysqli_query( $sql, sprintf("SELECT * FROM results WHERE run_id=%s;",
			mysqli_real_escape_string($sql, $i)));
		$results = array();
	
		while ( $row = mysqli_fetch_assoc($query) ) {
			array_push($results, $row);
		}

		$data['results'] = $results;
		$data['ip'] = '';

		array_push($sets, $data);
	}

	echo $json->encode($sets);
} else if ( isset($_REQUEST['data']) ){
	$data = $json->decode(str_replace('\\"', '"', $_REQUEST['data']));

	if ( $data ) {
		mysqli_query( $sql, sprintf("INSERT into runs VALUES(NULL,'%s','%s',NOW(),'%s');",
			mysqli_real_escape_string($sql, $_SERVER['HTTP_USER_AGENT']),
			mysqli_real_escape_string($sql, $_SERVER['REMOTE_ADDR']),
			mysqli_real_escape_string($sql, str_replace(';', "", $_REQUEST['style']))
		));

		$id = mysqli_insert_id($sql);

		if ( $id ) {

			$stmt = mysqli_prepare($sql, "INSERT INTO results VALUES(NULL,?,?,?,?,?,?,?,?,?,?,?)");

			foreach ($data as $row) {
				mysqli_stmt_bind_param($stmt, "isssidddddi",
							$id,
							$row->collection,
							$row->version,
							$row->name,
							$row->scale,
							$row->median,
							$row->min,
							$row->max,
							$row->mean,
							$row->deviation,
							$row->runs);
				mysqli_stmt_execute($stmt);
			}

			mysqli_stmt_close($stmt);

			echo $id;
		}
	}
}

mysqli_close($sql);
?>
