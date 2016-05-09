<?php
header('Content-type: text/html;charset=utf8');
#http://ddragon.leagueoflegends.com/cdn/6.9.1/img/champion/Aatrox.png
#http://ddragon.leagueoflegends.com/cdn/6.9.1/img/spell/FlashFrost.png
#http://ddragon.leagueoflegends.com/cdn/6.9.1/img/item/1001.png

echo '<link rel="stylesheet" href="style.css" />
<script type="text/javascript" src="http://api.holyhost.ru/jquery/jquery_1.4.2.min.js"></script>
';
function api($url){
	$api='api key here';
        $a=url_cache_read('https://global.api.pvp.net/api/lol/static-data/ru/v1.2/'.$url.'&api_key='.$api,'cache/'.str_replace(['?','&'],'_',$url));
	$a=json_decode($a,1);
	return $a;
}

$champs=api('champion?dataById=true&champData=all')['data'];
$items=api('item?itemListData=all')['data'];

$mages=[];
foreach($champs as $id=>$champ){
	if(!in_array('Mage',$champ['tags']))
		continue;

	if(in_array($id,[13,43,83,90]))
		continue;

	$hp=$champ['stats']['hp']+$champ['stats']['hpperlevel']*17;
	$resist=$champ['stats']['spellblock']+$champ['stats']['spellblockperlevel']*17;

	if(!explode('нанос',$champ['spells'][3]['sanitizedTooltip'])[1])
		continue;

	$form=explode('нанос',$champ['spells'][3]['sanitizedTooltip'])[1];
        preg_match_all("|{{ e[1-5] }} (.*)}}\%?\)(.*) урона|U",
		    $form,
		    $out, PREG_PATTERN_ORDER);
	$find=$out[0][0];
	if(!$find){
		$form=explode('нанос',$champ['spells'][3]['sanitizedTooltip'])[1];
	        preg_match_all("|урон(.*){{ e[1-5] }} (.*)}}\%?\)|U",
			    $form,
			    $out, PREG_PATTERN_ORDER);
		$find=$out[0][0];

	}
	if(!$find)
		continue;
	$true_damage=false;
	if($id==31)
		$true_damage=true;
	$health_damage=false;
	if($id==28)
		$health_damage=true;

        preg_match_all("|{{ e[1-5] }}(.*){{ a[1-5] }}\%?\)|U",
		    $find,
		    $out, PREG_PATTERN_ORDER);
	$find2=$out[0][0];

	if($id==55){
		$find2='{{ e1 }} (+{{ a1 }})';
	}
        if($id==96){
        	$find2='{{ e1 }} (+{{ a1 }})';
	}
	$e_num=(int)explode(' e',$find2)[1];
	$a_num=(int)explode(' a',$find2)[1];
	$var='';
	foreach($champ['spells'][3]['vars'] as $tmp){
		if($tmp['key']=='a'.$a_num)
			$var=$tmp['coeff'][0];
	}

        $mages[$id]=[
		'image'=>$champ['image'],
		'name'=>$champ['name'],
		'ult'=>$champ['spells'][3],
		'true_damage'=>$true_damage,
		'health_damage'=>$health_damage,
		'damage'=>['base'=>$champ['spells'][3]['effect'][$e_num],'ap'=>$var]
	];
}


function my_sort($a, $b) {
    if ($a['damage']['ap'] == $b['damage']['ap']) {
        return 0;
    }
    return ($a['damage']['ap'] < $b['damage']['ap']) ? -1 : 1;
}

uasort($mages,'my_sort');
$mages=array_reverse($mages,1);

echo '
<h1>Magic pen vs Magic resist</h1>
<div style="width:750px;float:left;border:1px solid gray;background:white;"><h2>Mages:</h2>';
foreach($mages as $id=>$champ){
	echo '<div class="champ" onclick="$(this).toggleClass(\'showpre\')" title="champ_id:'.$id.'">
		<div class="ico" style="background: url(\'http://ddragon.leagueoflegends.com/cdn/6.9.1/img/sprite/'.$champ['image']['sprite'].'\') -'.$champ['image']['x'].'px -'.$champ['image']['y'].'px no-repeat;"></div>
		<div style="display:inline-block;float:left;">'.$champ['name'].'</div>
	';
	echo '<br /><h5>'.implode('/',$champ['damage']['base']).' + '.($champ['damage']['ap']*100).'% ap</h5>';
	echo '</div>';

}

echo '</div>';
$mundo=[];
echo '<div style="width:750px;float:left;border:1px solid gray;background:white;"><h2>Tanks:</h2>';
foreach($champs as $id=>$champ){
	if(!in_array('Tank',$champ['tags']))
		continue;
	if($id!=36)
		continue;

	$hp=$champ['stats']['hp']+$champ['stats']['hpperlevel']*17;
	$resist=$champ['stats']['spellblock']+$champ['stats']['spellblockperlevel']*17;
	$mundo=['hp'=>$hp,'resist'=>$resist];
	echo '<div class="champ">
		<div class="ico" style="background: url(\'http://ddragon.leagueoflegends.com/cdn/6.9.1/img/sprite/'.$champ['image']['sprite'].'\') -'.$champ['image']['x'].'px -'.$champ['image']['y'].'px no-repeat;"></div>
		'.$champ['name'].'
		<br />HP: <i>'.$hp.'</i>
		<br />RESIST: <i>'.$resist.'</i>
	</div>';
}
echo '</div>';

echo '<div style="width:750px;float:left;border:1px solid gray;background:white;">';
$starting_items_tank=[3111,3102,3001,3065,3156,3512];
$final_stats=[];
foreach($items as $id=>$item){
	if(in_array($id,[3140,3155,2051,1033,1057,3211,3028]))
		continue;
	if(!in_array($id,$starting_items_tank))
		continue;
                
	if($item['tags'] && in_array('SpellBlock',$item['tags'])){
	echo '<div class="item" title="'.$item['name'].'">
		<div class="ico" style="background: url(\'http://ddragon.leagueoflegends.com/cdn/6.9.1/img/sprite/'.$item['image']['sprite'].'\') -'.$item['image']['x'].'px -'.$item['image']['y'].'px no-repeat;"></div>
		<br />HP: <i>+'.(int)$item['stats']['FlatHPPoolMod'].'</i>
		<br />resist: <i>+'.$item['stats']['FlatSpellBlockMod'].'</i>
	</div>';
        $final_stats['hp']+=$item['stats']['FlatHPPoolMod'];
        $final_stats['resist']+=$item['stats']['FlatSpellBlockMod'];
	}
}
echo '<br />All hp +'.$final_stats['hp'];
echo '<br />All resist +'.$final_stats['resist'];

echo '</div>';

$start_mpen=7.8;
$start_resist=1.34*9;

echo '<div style="width:750px;float:left;border:1px solid gray;background:white;">';
$starting_items_mage=[3001,3089,3135,3151,3020,3116];
$mage_ap=0;
foreach($items as $id=>$item){
	if(!in_array($id,$starting_items_mage))
		continue;

	if($item['tags'] /*&& in_array('MagicPenetration',$item['tags'])*/){
	echo '<div class="item" title="'.$item['name'].'">
		<div class="ico" style="background: url(\'http://ddragon.leagueoflegends.com/cdn/6.9.1/img/sprite/'.$item['image']['sprite'].'\') -'.$item['image']['x'].'px -'.$item['image']['y'].'px no-repeat;"></div>
		
	</div>';
	$mage_ap+=$item['stats']['FlatMagicDamageMod'];
        #$final_stats['hp']+=$item['stats']['FlatHPPoolMod'];
        #$final_stats['resist']+=$item['stats']['FlatSpellBlockMod'];
	}
}


$mage_ap=$mage_ap*1.35;
$perc_pen=0.35;
$pen=15+15+25+$start_mpen;
echo '<br />AP:'.$mage_ap;
echo '<br />percentage penetration: 35%';
echo '<br />flat magic penetration:'.$pen;

echo '</div>';

/** formula 100/(100+MR)
 **/


echo '<div style="width:750px;float:left;border:1px solid gray;background:white;">';
echo '<h1>Total damage to Mundo:</h1>';
echo '<br />Mage has starting '.$start_mpen.' mpen from runes';
echo '<br />Tank has starting '.$start_resist.' resist from runes';
$mundo['resist']+=$start_resist;
$mundo['resist']+=$final_stats['resist'];
$mundo['hp']+=$final_stats['hp'];
//$mundo['resist']=200;
echo '<br />All Mundo hp: '.$mundo['hp'];
echo '<br />All Mundo resist: '.$mundo['resist'].' block about '.round(100-100/(100+$mundo['resist'])*100,1).'% damage';
echo '</div>';

echo '<div style="width:850px;float:left;border:1px solid gray;background:white;">
';
foreach($mages as $id=>$champ){
	echo '<div class="champ" style="width:250px;" onclick="$(this).toggleClass(\'showpre\')" title="champ_id:'.$id.'">
		<div class="ico" style="background: url(\'http://ddragon.leagueoflegends.com/cdn/6.9.1/img/sprite/'.$champ['image']['sprite'].'\') -'.$champ['image']['x'].'px -'.$champ['image']['y'].'px no-repeat;"></div>
		<div style="display:inline-block;float:left;">'.$champ['name'].'</div>
	';

	echo '<br /><h5>Ult base damage: '.($champ['damage']['base'][2] .' + '.$champ['damage']['ap']*$mage_ap).'='.($champ['damage']['base'][2] + $champ['damage']['ap']*$mage_ap).'</h5>';
	if($champ['health_damage'])
		echo 'from %HP';
	if($champ['true_damage'])
		echo 'Deal true damage';
	$damage=($champ['damage']['base'][2] + $champ['damage']['ap']*$mage_ap);
	$damage_mod=100/(100+$mundo['resist']*(1-$perc_pen)-$pen);
	if($champ['true_damage'])
		$damage_mod=1;
	if($champ['health_damage'])
		$damage=$mundo['hp']*$damage/100;

	$damage*=$damage_mod;
        
	echo '<br />Tank block '.(int)((1-$damage_mod)*100).'%';
	echo '<br />Damage with penetration: '.(int)$damage;
	echo '<h3>Result: '.ceil($damage/$mundo['hp']*100).'% hp damage</h3>';
	echo '</div>';

}
echo '</div>';

function url_cache_read($url,$file){
	if(!file_exists($file)){
		$options  = array('http' =>
		array(
			'method'=>"GET"
		));
		$context  = stream_context_create($options);
		$a=file_get_contents($url, false, $context);
		if($a)
			file_put_contents($file,$a);
		return $a;
	}
	else return file_get_contents($file);
}
