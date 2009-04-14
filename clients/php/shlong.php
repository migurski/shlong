<?php

    function shlong_get_short_url($shlong_base, $long_url)
    {
        // first, check whether the requested long URL already has a short URL
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, rtrim($shlong_base, '/').'/?'.urlencode($long_url));
        
        $short_url = trim(curl_exec($c));
        curl_close($c);
        
        // if it doesn't, make one
        if(empty($short_url))
        {
            $c = curl_init();
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_URL, rtrim($shlong_base, '/').'/');
            curl_setopt($c, CURLOPT_POST, 1);
            curl_setopt($c, CURLOPT_POSTFIELDS, 'url='.urlencode($long_url));
        
            $short_url = trim(curl_exec($c));
            curl_close($c);
        }
        
        return $short_url;
    }

?>
