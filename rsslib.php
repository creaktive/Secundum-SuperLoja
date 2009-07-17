<?php
/*
	RSS Extractor and Displayer
	(c) 2007  Scriptol.com - Licence Mozilla 1.1.
	rsslib.php
	
	Requirements:
	- PHP 5.
	- A RSS feed.
*/

function strip($str, $size) {
	$str = strip_tags($str);
	$cut = substr($str, 0, $size);
	if (strlen($cut) != strlen($str)) {
		$pos = strrpos($cut, ' ');
		if ($pos !== false) {
			return substr($cut, 0, $pos) . '...';
		}
	}
	return $str;
}

$RSS_Content = array();

function RSS_Tags($item, $type)
{
		$y = array();
		$tnl = $item->getElementsByTagName("title");
		$tnl = $tnl->item(0);
		$title = $tnl->firstChild->data;

		$tnl = $item->getElementsByTagName("link");
		$tnl = $tnl->item(0);
		$link = $tnl->firstChild->data;

		$tnl = $item->getElementsByTagName("description");
		$tnl = $tnl->item(0);
		$description = $tnl->firstChild->data;

		$y["title"] = strip($title, 50);
		$y["link"] = $link;
		$y["description"] = strip($description, 150);
		$y["type"] = $type;
		
		return $y;
}


function RSS_Channel($channel)
{
	global $RSS_Content;

	$items = $channel->getElementsByTagName("item");
	
	// Processing channel
	
	/*
	$y = RSS_Tags($channel, 0);		// get description of channel, type 0
	array_push($RSS_Content, $y);
	*/
	
	// Processing articles
	
	foreach($items as $item)
	{
		$y = RSS_Tags($item, 1);	// get description of article, type 1
		array_push($RSS_Content, $y);
	}
}

function RSS_Retrieve($url)
{
	global $RSS_Content;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	$buf = curl_exec($ch);
	curl_close($ch);

	$doc  = new DOMDocument();
	$doc->loadXML($buf);

	$channels = $doc->getElementsByTagName("channel");
	
	$RSS_Content = array();
	
	foreach($channels as $channel)
	{
		 RSS_Channel($channel);
	}
	
}


function RSS_RetrieveLinks($url)
{
	global $RSS_Content;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	$buf = curl_exec($ch);
	curl_close($ch);

	$doc  = new DOMDocument();
	$doc->loadXML($buf);

	$channels = $doc->getElementsByTagName("channel");
	
	$RSS_Content = array();
	
	foreach($channels as $channel)
	{
		$items = $channel->getElementsByTagName("item");
		foreach($items as $item)
		{
			$y = RSS_Tags($item, 1);	// get description of article, type 1
			array_push($RSS_Content, $y);
		}
		 
	}

}


function RSS_Links($url, $size)
{
	global $RSS_Content;

	$page = "<ul>";

	RSS_RetrieveLinks($url);
	if($size > 0)
		$recents = array_slice($RSS_Content, 0, $size);

	foreach($recents as $article)
	{
		$type = $article["type"];
		if($type == 0) continue;
		$title = $article["title"];
		$link = $article["link"];
		$page .= "<li><a href=\"$link\">$title</a></li>\n";			
	}

	$page .="</ul>\n";

	return $page;
	
}



function RSS_Display($url, $size)
{
	global $RSS_Content;

	$opened = false;
	$page = "";

	RSS_Retrieve($url);
	if($size > 0)
		$recents = array_slice($RSS_Content, 0, $size);

	foreach($recents as $article)
	{
		$type = $article["type"];
		if($type == 0)
		{
			if($opened == true)
			{
				$page .="</ul>\n";
				$opened = false;
			}
			$page .="<b>";
		}
		else
		{
			if($opened == false) 
			{
				$page .= "<ul>\n";
				$opened = true;
			}
		}
		$title = $article["title"];
		$link = $article["link"];
		$description = $article["description"];
		$page .= "<li><a href=\"$link\">$title</a>";
		if($description != false)
		{
			$page .= "<br/>$description";
		}
		$page .= "</li>\n";			
		
		if($type==0)
		{
			$page .="</b><br/>";
		}

	}

	if($opened == true)
	{	
		$page .="</ul>\n";
	}
	return $page."\n";
	
}


?>
