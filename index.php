<?php

error_reporting(0);

$p1 = "XXXXXXXXX";  // preencha com seu cpf, apenas com os numeros

$busca = $_GET['busca'];
$filtro = $_GET['filtro'];
$completa = $_GET['completa'];

$chave = $_SERVER['HTTP_REFERER'];
$chave = str_replace("+","_",$chave); $chave = str_replace(" ","_",$chave);
if($chave) { $keyw = explode("q=",$chave); if($keyw[1]) { $chave = $keyw[1]; $keyw = explode("&",$chave); $chave = $keyw[0]; } else { $chave = ""; } }

$endereco = "http://secundum.com.br/superloja/?p1=".$p1."&p2=".$busca."&p3=".$filtro."&p4=".$completa."&p5=".$chave;

$ch = curl_init();$timeout = 15;
curl_setopt ($ch, CURLOPT_URL, $endereco);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$file_contents = curl_exec($ch);
curl_close($ch);

echo $file_contents;

?>