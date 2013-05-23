<?php
//db setup
$db = new PDO("sqlite:data/proxy.sq3");
$db->exec("CREATE TABLE proxy(id INTEGER PRIMARY KEY, user INTEGER UNIQUE NOT NULL, email TEXT, connected INTEGER UNIQUE)");