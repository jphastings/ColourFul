<?php
$phrase = stripslashes(urldecode($_GET['phrase']));
$max = 1024;

$themes = listThemes();
$methods = array("ASCII","Alphanumeric","URL","Integer");
?>
<html>
<head>
<title>ColourFul Encoder</title>
<style>
body {
    font-family: garamond;

}
.left {
    text-align:right;
    font-weight: bold;
    padding-right: 10px;
    width: 100px;
}
table {
    margin: 15px 0 25px 0;
}

ol li, ul li {
	padding-bottom:10px;
}

#content {
	width:600px;
	margin: 0 auto;
}

h1,#details {
	text-align:center;
	display:block;
	margin: 0 auto;
}
#details table {
	margin:0 auto;
}

#changes {
	margin:10px auto;
	width:600px;
}
#totoys {
	position:fixed;
	top:0;
	right:0;
}
#totoys img {
	border:0;
}
</style>
</head>
<body>
	<a href="http://toys.byJP.me/" id="totoys" title="Go to Toys by JP"><img src="http://toys.byJP.me/tl-toys.png" alt="Toys by JP"/></a>
<h1>ColourFul</h1>

<?php
if (strlen($phrase) > $max)
    $phrase = substr($phrase,0,$max);

if ($phrase != "") {
    $blocksize = (is_numeric($_GET['_bs'])) ?$_GET['_bs'] : ((is_numeric($_GET['bs'])) ? min(75,$_GET['bs']) : 40);

    $theme = (in_array($_GET['theme'],array_keys($themes))) ? $_GET['theme'] : "Rainbow";

    $code = "";

	switch($_GET['method']) {
		case "Alphanumeric":
			$requiredcolours = 6;
			$phrase = trim(preg_replace("![^a-z^0-9]!","",strtolower($phrase)));
			$chrs = preg_split('//', $phrase, -1, PREG_SPLIT_NO_EMPTY);
			$comment = "a-z, 0-9";
			foreach ($chrs as $chr) {
				if (preg_match("![a-z]!",$chr)) {
	                $minicode = ord($chr) - ord("a");
				} elseif (preg_match("![0-9]!",$chr)) {
					$minicode = 26 + $chr;
				}
                $code.=decbase($minicode,$requiredcolours);
			}
			break;

		case "URL":
			$requiredcolours = 7;
			function matchurlencode($matches) {
				return strtolower(urlencode($matches[0]));
			}
			$phrase = trim(preg_replace_callback("![^a-z^0-9^\\$^\%^&^\+^-^\.^/^:^;^=^\?^@^_]!",'matchurlencode',strtolower($phrase)));
			$chrs = preg_split('//', $phrase, -1, PREG_SPLIT_NO_EMPTY);
			
			$comment = "a-z 0-9 $ % & + - . / : = ? @ _";
			$extras = array("$"=>0,"%"=>1,"&"=>2,"+"=>3,"-"=>4,"."=>5,"/"=>6,":"=>7,"="=>8,"?"=>9,"@"=>10,"_"=>11);
			foreach ($chrs as $chr) {
				if (preg_match("![a-zA-Z]!",$chr)) {
	                $minicode = ord($chr) - ord("a");
				} elseif (preg_match("![0-9]!",$chr)) {
					$minicode = 27 + $chr;
				} else {
					$minicode = 37 + $extras[$chr];
				}
                $code.=decbase($minicode,$requiredcolours);
			}
			break;
		
		case "Integer":
			$requiredcolours = 10;
			$phrase = trim(preg_replace("![^0-9]!","",$phrase));
			$chrs = preg_split('//', $phrase, -1, PREG_SPLIT_NO_EMPTY);
			$comment = "0-9";
			foreach ($chrs as $chr) {
                $code.=$chr;
			}
			break;
		
		case "ASCII":
		default:
			$chrs = preg_split('//', trim($phrase), -1, PREG_SPLIT_NO_EMPTY);
			$requiredcolours = 16;
			$comment = "All ASCII characters (by their reference number)";
    		foreach ($chrs as $chr) {
		    	$code.=str_pad(dechex(ord($chr)),2,0,STR_PAD_LEFT);
		    }
	}
	
    $code = preg_split('//', $code, -1, PREG_SPLIT_NO_EMPTY);
	if ($phrase != "") {
		
		$possibleWidths = array();
		for($possibleWidth=count($code);$possibleWidth >= sqrt(count($code));$possibleWidth--) {
			if (count($code) % $possibleWidth == 0)
				$possibleWidths[$possibleWidth] = $possibleWidth;
		}
		$square = end($possibleWidths);
				
	    foreach(array_reverse($possibleWidths) as $p) {
			$w = count($code) / $p;
			$possibleWidths[$w] = $w;
		}
		
		$width = (in_array($_GET['width'],$possibleWidths)) ? $_GET['width'] : $square;
	
	    $height = count($code) / $width;

		if ($height*$blocksize < $requiredcolours) {
			$blocksize = ceil($requiredcolours/$height);
		}

	    $key = ($height*$blocksize) / ($requiredcolours);
	
		$key = ($_GET['strict'] == 1) ? floor($key) : $key;

	    $img = imagecreatetruecolor($width*$blocksize + $key+1,$height*$blocksize);

		$colours = array();
		$thistheme = $themes[$theme];

		$offset = (($_GET['offset'] >= 0 and $_GET['offset'] <= 16) ? $_GET['offset'] : 0);

		for($ii=0;$ii<$offset;$ii++) {
			$thistheme[] = array_shift($thistheme);
		}

		$thistheme = doSpread($thistheme,$_GET['spread']);

		$black = imagecolorallocate($img, 0, 0, 0);
	    foreach($thistheme as $i=>$allcolour) {
			if ($i >= $requiredcolours)	
				break;
	        $c = explode(",",$allcolour);
	        $colours[] = imagecolorallocate($img,$c[0],$c[1],$c[2]);

	    }

	    foreach($code as $i=>$colour) {
	        $colour = hexdec($colour);
	        paint($i,$img,$colours[$colour]);
	    }

	    foreach(array_merge($colours,array($black)) as $i=>$colour) {
	        imagefilledrectangle($img, $width * $blocksize + 1, $i * $key, $width * $blocksize + $key, ($i+1) * $key, $colour);
	    }
	    imageline($img, $width * $blocksize, 0, $width * $blocksize, $height * $blocksize, $black);

	    $fname = explode(" ",$phrase);
	    $fname = preg_replace("!_{2,}!", "_", preg_replace("!\W!", "_", $fname[0]));

	    ob_start();
	    imagepng($img);
	    $image = base64_encode(ob_get_contents());
	    ob_end_clean();
	
    	?>

		<div id="details">
			<img src="data:image/png;base64,<?php echo $image?>" id="encoding"></a>
			<?php } else { ?>
			<em>Ooops, with that conversion method there are no characters to convert!</em>
			<?php } ?>
		<table>
	    <tr>
	        <td class="left">Phrase:</td>
	        <td><?=$phrase?> <small style="font-style:italic;">[<?=strlen($phrase)?> chars]</small></td>
	    </tr>
	    <tr>
	        <td class="left">Width:</td>
	        <td><?=$width?> blocks</td>
	    </tr>
	    <tr>
	        <td class="left">Height:</td>
	        <td><?=$height?> blocks</td>
	    </tr>
	    <tr>
	        <td class="left">Block Size:</td>
	        <td><?=$blocksize?> px</td>
	    </tr>
	    <tr>
	        <td class="left">Key Width:</td>
	        <td>~<?=floor($key)?> px</td>
	    </tr>
		<tr>
	        <td class="left">Character Conversion:</td>
	        <td><?=$comment?></td>
	    </tr>
		<?php if ($_GET['save'] == 1) { $url = file_get_contents("http://tinyurl.com/api-create.php?url=data:image/png;base64,".$image);?>
		<tr>
			<td class="left">Image URL:</td>
			<td><a href="<?php echo $url?>"><?php echo $url?></a></td>
		</tr>
		<?php }?>
	</table>
</div>
    <?php
}

// Page
?>
<form action="./" method="get">
<table id="changes">
	<tr>
		<td style="width:300px">
			<textarea name="phrase" style="width:100%;height:95px;"><?=stripslashes($_GET['phrase'])?></textarea>
		</td>
		<td style="width:300px;padding-left:5px;height:95px">
		Palette: <select name="theme">
			<?php foreach(array_keys($themes) as $theme) { ?>
			<option value="<?php echo $theme?>"<?php if ($_GET['theme'] == $theme) { echo " selected=\"true\"";} ?>><?php echo $theme?></option>
			<?php } ?>
		</select><br/>
		Conversion Method: 
		<select name="method">
			<?php foreach($methods as $method) { ?>
			<option value="<?php echo $method?>"<?php if ($_GET['method'] == $method) { echo " selected=\"true\"";} ?>><?php echo $method?></option>
			<?php } ?>
		</select><br/>
		Offset: <select name="offset">
			<?php for($ii=0;$ii<16;$ii++) { ?>
			<option value="<?php echo $ii?>"<?php if ($_GET['offset'] == $ii) { echo " selected=\"true\"";} ?>><?php echo $ii?></option>
			<?php } ?>
		</select> 
		Spread: <select name="spread">
			<?php for($ii=0;$ii<3;$ii++) { ?>
			<option value="<?php echo $ii?>"<?php if ($_GET['spread'] == $ii) { echo " selected=\"true\"";} ?>><?php echo $ii?></option>
			<?php } ?>
		</select><br/>
		Block Size: <input style="width:50px" type="text" value="<?php echo ((isset($blocksize))? $blocksize : 40); ?>" name="bs"/> <input type="checkbox" name="aid" value="1"<?php if (isset($_GET['aid'])) { echo " checked=\"true\"";} ?>>Add Colour Aid</input><br/>
		Dimensions: <select name="width">
			<?php foreach($possibleWidths as $p) { ?>
			<option value="<?php echo $p?>"<?php if ($width == $p) { echo " selected=\"true\"";} ?>><?php echo $p." x ".(count($code)/$p)?></option>
			<?php } ?>
		</select>
	</td>
	</tr>
	<tr>
		<td colspan="2" style="text-align:center">
			<input type="submit" value="Encode"/><?php if ($phrase != "") { ?> <input type="hidden" name="save" value = "0" id="dosave" title="Save the image by mapping its data to a URL on a shortening service (TinyURL.com)"><input type="submit" value="Save Image" onclick="document.getElementById('dosave').value=1;"/><?php } ?>
			</td>
		</tr>
	</table>
</form>

<div id="content">
<h2>Why?</h2>
<p>I wanted to build a way of turning text into something (vaguely) pretty, devoid of obvious meaning but easily reformed back into text by hand &mdash; this is what I came up with!</p>

<h2>How?</h2>
<ol>
	<li>A range of letters will be converted. At the moment there are four ranges: <a target="wiki" href="http://en.wikipedia.org/wiki/ASCII">ASCII</a>, <a target="wiki" href="http://en.wikipedia.org/wiki/URL">URL</a>, (positive) <a target="wiki" href="http://en.wikipedia.org/wiki/Integer">integer</a> and (case insensitive) <a target="wiki" href="http://en.wikipedia.org/wiki/Alphanumeric">alphanumeric</a>.</li>
	<li>Each character is converted into a number according to the chosen method (The number is represented in either base 6 (Alphanumeric), 7 (URL), 10 (Integer) or 16 (ASCII) depending on the method).</li>
	<li>More than 16 colours can be hard to tell apart, so for clarity all except the numerical method split each character number into two numbers. eg. 255 in base 16 (ASCII) becomes <em>[ 16, 16 ]</em>; 27 in base 7 (URL) becomes <em>[ 3, 6 ]</em> &mdash; have a look at <a href="http://en.wikipedia.org/wiki/Base_%28mathematics%29">bases on the wikipedia</a> for more info.</li>
	<li>Each number is now converted to a colour according to the selections you make</li>
	<li>The colours are laid out in order (in a rectangle), and a 'key' is placed on the right so you can convert the colours back into numbers (the top colour = 0, the next = 1 and so on).</li>
	<li>You have a colourful picture that has a clearly interpretable message inside it!</li>
</ol>
<h3>Notes about the conversion methods</h3>
	<p>NB. Look at the 'character conversion' information above, it gives you the list of characters in order so you can convert back from a coloursheet</p>
	<ul>
		<li>ASCII is one of the original charcter indexing schemes where every character needed (at the time) was given a number ('a' is 97 for example). There are alot of useless characters, so your message make look strange.</li>
		<li>Alphanumeric only allows the letters a-z (which become 0-26) and numbers (that become 27-36) which means they can be described by two base-6 numbers, so two squares = one character with 6 different colours for each square.</li>
		<li>URLs only have a certain number of allowed characters. I picked the alphabet, numbers and 13 most popular symbols (arranged in ASCII order) to make 49 symbols. This makes two base-7 numbers, therefore two squares are one character and there are 7 possible colours for each square. You can enter <em>any</em> character, but if it is not present in the above 49 it will be urlencoded and will look like: %20 (which is a space).</li>
		<li>Integer is as it sounds, there are 10 colours, the first is 0, the last is 9!</li>
	</ul>

<?php

// Functions
function paint ($i,$img,$colour) {
	global $width;
	global $blocksize;
	global $requiredcolours;

	$y = floor($i / $width);
	$x = $i % $width;
	// Square
	imagefilledrectangle($img, $x * $blocksize, $y * $blocksize, ($x + 1) * $blocksize, ($y +1) * $blocksize, $colour);
	if (isset($_GET['aid']) and $blocksize >= 16) {
		global $colours;
		
		for($i=0;$i<$requiredcolours;$i++) {
			//$the_y = ($y % 2) ? ($y+1) * $blocksize - 1 : $y * $blocksize;
			//imagesetpixel($img,$x*$blocksize + $i,$the_y,$colours[$i]);
			if (($x % 2))
				imagesetpixel($img,$x*$blocksize,$y*$blocksize + $i,$colours[$i]);
			if (($x+1 == $width) and !($x % 2))
				imagesetpixel($img,($x+1)*$blocksize-1,$y*$blocksize + $i,$colours[$i]);
		}
	}
}

function listThemes() {
	$themes = array();
	if ($handle = opendir("./")) {
		while (false !== ($file = readdir($handle))) {
			$path = pathinfo($file);
			if ($path['extension'] == "theme") {
				$themes[substr($path['basename'],0,-6)] = explode("\n", file_get_contents($file));
			}
		}
		closedir($handle);
	}
	return $themes;
}

function decbase($number,$base) {
	$a = dechex(floor($number / $base));
	$b = dechex($number % $base);
	return $a.$b;
}

function doSpread($thistheme,$spread) {
	switch($spread) {
		case 2:
			for($i=1;$i<16;$i+=3) {
				$thistheme[] = $thistheme[$i];
				unset($thistheme[$i]);
			}
			$thistheme = array_values($thistheme);
			for($i=1;$i<16;$i+=2) {
				$thistheme[] = $thistheme[$i];
				unset($thistheme[$i]);
			}
			break;
		case 1:
			for($i=1;$i<16;$i+=2) {
				$thistheme[] = $thistheme[$i];
				unset($thistheme[$i]);
			}
			break;
	}
	return array_values($thistheme);
}
?>
</div>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-8694035-2");
pageTracker._trackPageview();
} catch(err) {}</script>
</body>
</html>
