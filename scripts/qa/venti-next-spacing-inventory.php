<?php
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app=require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$out=[];
foreach(app('router')->getRoutes() as $r){if(!in_array($r->getName(),config('venti-next.routes')))continue;
$a=$r->getActionName();$views=[];
if(strpos($a,'@')!==false){list($c,$m)=explode('@',$a);$ref=new ReflectionMethod($c,$m);$code=implode('',array_slice(file($ref->getFileName()),$ref->getStartLine()-1,$ref->getEndLine()-$ref->getStartLine()+1));preg_match_all('/view\(\s*[\'\"]([^\'\"]+)/',$code,$matches);$views=$matches[1];}
$out[]=['name'=>$r->getName(),'uri'=>$r->uri(),'action'=>$a,'views'=>$views];}
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
