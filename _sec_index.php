<?php

error_reporting(0);


        $idml = "XXXXX";  // preencha com seu id de Mecado S�cio
     $urlloja = "XXXXX";  // prencha com a url completa (sem esquecer do http://) da loja e terminando com uma /
	
$busca = $_GET['busca'];
$filtro = $_GET['filtro'];


/* Desenvolvido por Jobson Lemos Batista (http://jobsonlemos.com)

Instru��es gerais

1. Preencha os dados acima, substituindo os termos como descrito.
2. Crie uma pasta em seu site (Ex.: http://seusite.com/loja/)
3. Fa�a o Upload dos dois arquivos (_sec_index.php e .htaccess) para essa pasta
4. Altere o nome de _sec_index.php para index.php
5. Modifique as �reas em html a seu gosto, inclusive o css
6. IMPORTANTE: n�o mexa nas �reas de php a menos que saiba o que est� fazendo
7. Depois de instalado, as chamadas de url ficam assim... Exemplo: http://seusite.com/loja/ipod_nano/vis/
8. ipod_nano >> o produto sendo buscado. Quando se tratar de mais de uma palavra, elas devem ser separadas por _
9. vis >> O sistema tem quatro filtros:
		
		vis = mais visitados
		ven = mais vendidos
		bar = mais baratos
		car = mais caros

10. Qualquer d�vida, � s� me escrever ou me acrescentar no messenger

		contato@jobsonlemos.com (e-mail)
		jobsonlb@hotmail.com    (msn)

11. E n�o esque�a de colaborar com o trabalho indicando para amigos e me linkando em seu site (http://jobsonlemos.com e http://secundum.com.br)...
12. IMPORTANTE 2 - O sistema utilia a fun��o curl() do php. E recomendo o php5. Se sua hospedagem n�o utiliza ou n�o instala para voc�... honestamente, mude de hospedagem.
    O Janio Samento, dono da PortoF�cil (http://portofacil.net/) tem planos excelentes que acomodam perfeitamente suas necessidades. Fale com ele
13. A vantagem desse sistema em rela��o a outras lojas. Desenvolvimento constante em busca de resultados. Baixa utiliza��o do servidor uma vez que o processamento mais pesado � realizado pelo meu servidor.
15. Seja feliz, fa�a boas vendas e sucesso.

*/

// N�o mexa que pode causar danos

$chave = $_SERVER['HTTP_REFERER'];
$chave = str_replace("+","_",$chave); $chave = str_replace(" ","_",$chave);
if($chave) { $keyw = explode("q=",$chave); if($keyw[1]) { $chave = $keyw[1]; $keyw = explode("&",$chave); $chave = $keyw[0]; } else { $chave = ""; } }
$endereco = "http://secundum.com.br/parceiros/superloja.php?id_ml=".$idml."&busca=".$busca."&url=".urlencode($urlloja)."&filtro=".$filtro."&chave=".$chave;

$ch = curl_init();$timeout = 15;
curl_setopt ($ch, CURLOPT_URL, $endereco);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$file_contents = curl_exec($ch);
curl_close($ch);
$buffer = explode("###",$file_contents);

?>



<html>
<head>
<style>
<!--
img { border:0px; }
div#loja { width:800px; margin:auto; }
div#corpo { width:600px; float:left; }
div#lateral { width:180px; float:right; }
li#item_menu { display:block; padding:3px; font-family:Tahoma; font-size:11px; }
li#item_menu a { text-decoration:none; padding:3px; }
li#item_menu a:hover { background-color:#000000; color:#ffffff; padding:3px; font-size:13px; font-weight:bold; }
li#item_menu h3 { font-size:11px; margin:0px; padding: 0px; }
div#produto { width:30%; float:left; padding:5px; border: 2px solid #eeeeee; margin:1px; text-align:center; background:#ffffff; font-family:Tahoma,Verdana,Arial; font-size:9px; color:#000000; }
span#texto { height:70px; display:block; line-height:11px; }
span#imagem { height:100px; display:block; text-decoration:none; }
div#valores { width:75%; float:left; }
span#preco { font-size:15px; font-weight: bold; color:#990000; display:block; }
div#comprar { display:block; width:25%; float:left; }
-->
</style>

<?php echo $buffer[0]; 
$busca = str_replace("_mel.html","",$busca); 
$busca = str_replace("_bar.html","",$busca);
$busca = str_replace("_car.html","",$busca);
$busca = str_replace("_vis.html","",$busca);
$busca = str_replace("_ven.html","",$busca);
$busca = str_replace("_"," ",$busca); ?>

<title><?php echo $busca; ?> : Shopping</title>

</head>

<body>


<?php
// �rea principal. N�o mexa que explode...

echo $buffer[1];
?>



</body>
</html>