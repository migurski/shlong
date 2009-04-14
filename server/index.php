<?php

    // Your MySQL database connection string
    $dsn = 'mysql://<username>:<password>@<hostname>/<database>';
    
    class Context
    {
        var $db; // resource
        
        function Context($dsn)
        {
            if(preg_match('#^mysql://(\w+):(\w+)@(.+)/(\w+)$#i', $dsn, $parts))
            {
                list($dsn, $username, $password, $hostname, $database) = $parts;
                $this->db = mysql_connect($hostname, $username, $password);
                mysql_select_db($database, $this->db);
            }
        }
        
        function addURL($long_url)
        {
            for($i = 1; $i <= 16; $i += 1)
            {
                $short_url = $this->baseDir().'/'.generate_id($i);
            
                $q = sprintf("INSERT INTO urls
                              SET short_url='%s', long_url='%s'",
                             mysql_real_escape_string($short_url, $this->db),
                             mysql_real_escape_string($long_url, $this->db));

                if(mysql_query($q, $this->db))
                    return $short_url;

                // duplicate short URL, we try again
                if(mysql_errno($this->db) == 1062)
                    continue;
                
                die('MySQL error: '.mysql_errno($this->db));
            }
        }
        
        function getLongURL($short_url)
        {
            $q = sprintf("SELECT long_url FROM urls
                          WHERE short_url='%s'",
                         mysql_real_escape_string($short_url, $this->db));

            $r = mysql_query($q, $this->db);
            
            if($row = mysql_fetch_assoc($r))
                return $row['long_url'];

            return false;
        }
        
        function getShortURL($long_url)
        {
            $q = sprintf("SELECT short_url FROM urls
                          WHERE long_url='%s'",
                         mysql_real_escape_string($long_url, $this->db));

            $r = mysql_query($q, $this->db);
            
            if($row = mysql_fetch_assoc($r))
                return $row['short_url'];

            return false;
        }
        
        function baseDir()
        {
            return "http://{$_SERVER['SERVER_NAME']}".dirname($_SERVER['SCRIPT_NAME']);
        }
        
        function currentURL()
        {
            return "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
        }
        
        function close()
        {
            mysql_close($this->db);
        }
    }
   
    function generate_id($length)
    {
        $chars = 'qwrtypsdfghjklzxcvbnm0123456789';
        $id = '';
        
        while(strlen($id) < $length)
            $id .= substr($chars, rand(0, strlen($chars) - 1), 1);

        return $id;
    }
    
    $context = new Context($dsn);
    
    header('Content-Type: text/plain');
    header('X-Current-URL: '.$context->currentURL());
    
    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        if($long_url = $_POST['url'])
        {
            $short_url = $context->getShortURL($long_url);
            
            if(empty($short_url)) {
                $short_url = $context->addURL($long_url);
                header('HTTP/1.0 201');

            } else {
                header('HTTP/1.0 200');
            }

            $context->close();
            exit($short_url."\n");
        }
    }
    
    if($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        if($long_url = $context->getLongURL($context->currentURL()))
        {
            $context->close();
            header('HTTP/1.0 301');
            header('Location: '.$long_url);
            exit($long_url."\n");
        }

        if($short_url = $context->getShortURL($_SERVER['QUERY_STRING']))
        {
            $context->close();
            exit($short_url."\n");
        }

        $context->close();
        header('HTTP/1.0 404');
    }

?>
