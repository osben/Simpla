<?php

/**
 * Simpla CMS
 *
 * @copyright	2017 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once('api/Simpla.php');

class LicenseAdmin extends Simpla
{

    public function fetch()
    {
        if ($this->request->method('POST')) {
            $license = $this->request->post('license');
            $this->config->license = trim($license);
        }

        $p=11;
        $g=2;
        $x=7;
        $r = '';
        $s = $x;
        $bs = explode(' ', $this->config->license);
        foreach ($bs as $bl) {
            for ($i=0, $m=''; $i<strlen($bl)&&isset($bl[$i+1]); $i+=2) {
                $a = base_convert($bl[$i], 36, 10)-($i/2+$s)%26;
                $b = base_convert($bl[$i+1], 36, 10)-($i/2+$s)%25;
                $m .= ($b * (pow($a, $p-$x-1))) % $p;
            }
            $m = base_convert($m, 10, 16); $s+=$x;
            for ($a=0; $a<strlen($m); $a+=2) {
                $r .= @chr(hexdec($m{$a}.$m{($a+1)}));
            }
        }

        @list($l->domains, $l->expiration, $l->comment) = explode('#', $r, 3);

        $l->domains = explode(',', $l->domains);

        $h = getenv("HTTP_HOST");
        if (substr($h, 0, 4) == 'www.') {
            $h = substr($h, 4);
        }
        $l->valid = true;
        if (!in_array($h, $l->domains)) {
            $l->valid = false;
        }
        if (strtotime($l->expiration)<time() && $l->expiration!='*') {
            $l->valid = false;
        }


        $this->design->assign('license', $l);
        
        if (! $l->valid) {
            $end = date("Y-m-d", time()+60*60*24*31); // на 31 день
            $c = '';
            for($i=0; $i<rand(30, 40); $i++)
            {
                $c .= rand(0,9);
            }
            $license = $h.'#'.$end.'#'.$c;
            $p = 11;
            $g = 2;
            $x = 7; //rand(2, $p-1);
            $y = pow($g, $x) % $p; // same as x

            $message = bin2hex($license);
            $message = str_split($message, 4);

            $key = '';
            $shift = $x;

            foreach($message as $block)
            {	
	            $block = base_convert($block, 16, 10);
	            $enc_block = '';
	            for($i = 0; $i < strlen($block); $i++)
	            {
		            $k = rand(2, $p - 1);
		            $a = pow($g, $k) % $p + ($i + $shift) % 26;
	            	$b = ( (pow($y % $p, $k) % $p) * ($block[$i]) ) % $p + ($i + $shift) % 25;
		
	            	$enc_block .= base_convert($a, 10, 36).base_convert($b, 10, 36);
	            }
	            $key .= $enc_block.' ';
	            $shift += $x;
            }

			$this->design->assign('testlicense', $key);
		}

        return $this->design->fetch('license.tpl');
    }
}
