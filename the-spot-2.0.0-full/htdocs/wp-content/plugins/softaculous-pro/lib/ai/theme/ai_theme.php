<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

function ai_theme(){
	global $user, $globals, $l, $theme, $softpanel, $error, $insid, $software, $soft, $iscripts;
	global $softpath, $custom_path;
	
	$insid = GET('insid', '');
	$custom_path_get = GET('path', '');
	$project_id = GET('project_id', '');
	$username = (!empty($softpanel->user['name']) ? $softpanel->user['name'] : (!empty($user['username']) ? $user['username'] : ''));
	
	$project_name = '';
	if(!empty($project_id)){
		include_once(dirname(__DIR__).'/ai_launcher.php');
		ai_php_init_classes();
		require_once(dirname(__DIR__).'/core/class_project.php');
		$_proj = AIProject::load($username, $project_id);
		if($_proj && !empty($_proj['name'])) $project_name = $_proj['name'];
	}elseif(!empty($insid)){
		$project_name = !empty($software['name']) ? $software['name'] : '';
	}
	
	$homedir = !empty($softpanel->user['homedir']) ? $softpanel->user['homedir'] : ai_get_homedir($username);
	
	if(!empty($insid)){
		$data = $user['ins'][$insid];
		$softpath = !empty($data['softpath']) ? $data['softpath'] : '';
		$softurl = !empty($data['softurl']) ? $data['softurl'] : '';
		$sw_name = !empty($software['name']) ? $software['name'] : 'Software';
		$back_url = $globals['ind'].'act=editdetail&insid='.$insid;
	}elseif(!empty($custom_path_get)){
		$softpath = $custom_path_get;
		$softurl = '';
		$sw_name = basename($custom_path_get);
		$back_url = $globals['ind'].'act=ai';
	}elseif(!empty($softpath)){
		$softurl = '';
		$sw_name = basename($softpath);
		$back_url = $globals['ind'].'act=ai';
	}else{
		$softpath = '';
		$sw_name = 'Select Project';
		$back_url = '';
	}
	
	if(!empty($project_name)) $sw_name = $project_name;
	
	$csrf = '';
	if(function_exists('csrf_display')){
		ob_start();
		csrf_display();
		$csrf_html = ob_get_clean();
		if(preg_match('/value="([^"]+)"/', $csrf_html, $m)){
			$csrf = $m[1];
		}
	}

	$api_base = $globals['index'].'act=ai';
	if(!empty($insid)) $api_base .= '&insid='.$insid;
	if(!empty($custom_path)) $api_base .= '&path='.urlencode($custom_path);
	if(!empty($project_id)) $api_base .= '&project_id='.urlencode($project_id);

	ob_start();
	softheader(__('$0 - Code with AI', array($sw_name)));
	ob_end_clean();

	$installations = array();
	if(!empty($user['ins'])){
		foreach($user['ins'] as $iid => $idata){
			$isoft = get_sid_by_version($idata['ver'], $idata['sid']);
			$isoftware = !empty($iscripts[$isoft]) ? $iscripts[$isoft] : array('name' => 'Software');
			$installations[] = array('insid' => $iid, 'name' => $isoftware['name'], 'path' => $idata['softpath']);
		}
	}

	ai_render_ui($api_base, $csrf, $softpath, $sw_name, $username, $back_url, $insid, $installations, $homedir, $project_id);
	die();
}

function ai_render_ui($api_base, $csrf, $softpath, $sw_name, $username, $back_url, $insid, $installations = array(), $homedir = '', $project_id = ''){
	
global $globals, $theme;
	
$custom_favicon = (!empty($globals['favicon_logo']) ? $globals['favicon_logo'] : '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($sw_name); ?> - Code with AI</title>
<link rel="shortcut icon" href=<?php echo $custom_favicon; ?> />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" id="hljs-theme">
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/12.0.2/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.1.6/purify.min.js"></script>
<style>
:root{
  --bg:#F9FAFB;--bgW:#F3F4F6;--bgS:#FFFFFF;--bgSs:#F9FAFB;
  --t1:#111827;--t2:#6B7280;--t3:#9CA3AF;--t4:#D1D5DB;
  --p:#3B82F6;--pH:#2563EB;--pT:#3B82F6;--s:#8B5CF6;
  --bd:#E5E7EB;--bd2:#EAEBEE;--bd3:#F3F4F6;
  --in:#FFFFFF;--ok:#22C55E;--err:#EF4444;--wr:#F59E0B;
  --sb:280px;--hh:56px;--r:12px;--rS:8px;--rP:9999px;
--sans:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
--mono:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace
 }
 @keyframes ocFadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
 @keyframes ocSlideIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
 @keyframes ocPulse{0%,100%{opacity:1}50%{opacity:.4}}
 [data-cs="dark"]{
  --bg:#09090B;--bgW:#121315;--bgS:#0C0C0E;--bgSs:#111114;
  --t1:#F3F4F6;--t2:#A1A1AA;--t3:#71717A;--t4:#52525B;
  --p:#60A5FA;--pH:#93C5FD;--pT:#60A5FA;--s:#A78BFA;
  --bd:#52525B;--bd2:#3F3F46;--bd3:#27272A;
  --in:#131316;--ok:#4ADE80;--err:#F87171;--wr:#FBBF24
}
*{box-sizing:border-box;margin:0;padding:0}
html{height:100%}
body{font-family:var(--sans);font-size:14px;background:var(--bg);color:var(--t1)}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--bd);border-radius:99px}

#oc{display:flex;flex-direction:column;height:100vh}

/* Header */
.ocH{display:flex;align-items:center;height:var(--hh);padding:0 20px;background:var(--bgS);border-bottom:1px solid var(--bd2);flex-shrink:0;gap:4px;backdrop-filter:blur(12px)}
.ocHl,.ocHr{display:flex;align-items:center;gap:6px;flex-shrink:0}
.ocHc{flex:1;display:flex;justify-content:center;align-items:center;gap:10px;overflow:hidden}
.ocIb{width:36px;height:36px;border:none;border-radius:var(--rS);background:transparent;color:var(--t3);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:17px;transition:all .2s}
.ocIb:hover{background:var(--bgW);color:var(--t1)}
.ocB{height:36px;padding:0 16px;border:1px solid var(--bd);border-radius:var(--rP);background:var(--bgS);color:var(--t1);cursor:pointer;font-weight:500;font-size:13px;font-family:var(--ff);display:inline-flex;align-items:center;gap:6px;white-space:nowrap;transition:all .2s cubic-bezier(.4,0,.2,1);text-decoration:none}
.ocB:hover{background:var(--bgW);border-color:var(--t4);transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.ocBP{background:var(--p);border-color:var(--p);color:#fff}
.ocBP:hover{background:var(--pH);border-color:var(--pH);box-shadow:0 6px 20px rgba(59,130,246,.25)}
.ocBs{height:32px;padding:0 12px;font-size:12px}
.ocSel{line-height:1.3 !important;height:36px;padding:0 28px 0 12px;border:1px solid var(--bd);border-radius:var(--rP);background:var(--bgS);color:var(--t1);font:400 13px/1 var(--ff);cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239CA3AF' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;transition:border-color .2s}
[data-cs="dark"] .ocSel{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23A1A1AA' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E")}
.ocSel:hover{border-color:var(--t4)}.ocSel:focus{outline:none;border-color:var(--p)}
.ocSel option{background:var(--bgS);color:var(--t1)}

/* Body */
.ocBd{display:flex;flex:1;overflow:hidden}

/* Sidebar */
.ocSb{width:var(--sb);background:var(--bgS);border-right:1px solid var(--bd2);display:flex;flex-direction:column;flex-shrink:0;overflow:hidden;transition:width .2s cubic-bezier(.4,0,.2,1)}
.ocSb.hid{width:0;border-right:none}
.ocSb.resp{position:fixed;left:0;top:var(--hh);height:calc(100vh - var(--hh));z-index:200;box-shadow:4px 0 24px rgba(0,0,0,.1)}
.ocSbH{padding:14px 20px;font-weight:500;font-size:11px;font-family:var(--ff);letter-spacing:.08em;text-transform:uppercase;color:var(--t3);border-bottom:1px solid var(--bd3);display:flex;justify-content:space-between;align-items:center}
.ocSbL{flex:1;overflow-y:auto;padding:8px 0}
.ocSi{display:flex;align-items:center;padding:8px 20px;cursor:pointer;color:var(--t2);gap:8px;transition:all .15s;position:relative;border-radius:0}
.ocSi:hover{background:var(--bgW);color:var(--t1)}
.ocSi.act{background:var(--bgW);color:var(--t1)}
.ocSi.act::before{content:'';position:absolute;left:0;top:8px;bottom:8px;width:3px;border-radius:0 3px 3px 0;background:var(--p)}
.ocSiI{flex-shrink:0;width:16px;text-align:center;font-size:13px;color:var(--t3);opacity:.7}
.ocSiT{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px}
.ocSiB{font-size:10px;background:var(--bd3);color:var(--t3);padding:2px 8px;border-radius:var(--rP);flex-shrink:0;font-weight:500}
.ocSiD{opacity:0;border:none;background:none;color:var(--t3);cursor:pointer;font-size:14px;padding:0 2px;flex-shrink:0;line-height:1;transition:opacity .15s}
.ocSi:hover .ocSiD{opacity:.6}.ocSiD:hover{color:var(--er)!important;opacity:1}

/* Responsive */
@media(max-width:1000px){
.ocH{padding:0 12px;gap:2px}
.ocHl{gap:2px}
.ocHr{gap:2px;flex-wrap:wrap;justify-content:flex-end}
.ocB.ocBs{padding:0 8px;font-size:11px}
.ocHb{display:none}
.ocBtnTxt{display:none}
.ocB.ocBs:hover .ocBtnTxt{
  display:inline-block;position:absolute;left:50%;transform:translateX(-50%);
  background:#1a1a1a;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;white-space:nowrap;z-index:1000;margin-top:28px
}
.ocB.ocBs{position:relative}
}

/* Main */
.ocMn{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;background:var(--bg)}
.ocC{flex:1;overflow-y:auto}
.ocCi{max-width:720px;margin:0 auto;padding:24px}

/* Context bar */
.ocCtx{padding:16px 24px 0;max-width:720px;margin:0 auto}
.ocCtxI{padding:10px 16px;background:var(--bgW);border:1px solid var(--bd2);border-radius:var(--r);font-size:12px;color:var(--t2);line-height:1.6}
.ocCtxI b{color:var(--t1);font-weight:500}
.ocCtxI code{background:var(--bgS);padding:1px 4px;border-radius:4px;font-family:var(--fm);font-size:11px}

/* Empty state */
.ocE{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--t3);gap:16px;padding:48px 32px;text-align:center}
.ocEi{font-size:44px;opacity:.3}.ocEt{font-size:20px;color:var(--t2);font-weight:500}.ocEs{font-size:14px;line-height:1.6;max-width:400px;color:var(--t3)}

/* Messages */
.ocMsg{padding:16px 0;display:flex;gap:12px;animation:ocFadeIn .25s ease-out}
.ocMsg.u{flex-direction:row-reverse}
.ocAv{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
.ocAv svg{width:18px;height:18px}
.ocMsg.u .ocAv{background:var(--p);color:#fff}
.ocMsg.a .ocAv{background:var(--bgW);color:var(--t3);border:1px solid var(--bd)}
.ocMb{max-width:90%;min-width:0}
.ocMbb{padding:0;line-height:1.65;font-size:14.5px}
.ocMsg.u .ocMbb{padding:12px 16px;background:var(--p);color:#fff;border-radius:var(--r);border-bottom-right-radius:4px;font-size:14.5px;box-shadow:0 2px 8px rgba(59,130,246,.2)}
[data-cs="dark"] .ocMsg.u .ocMbb{background:var(--p)}
.ocMsg.u .ocMd,.ocMsg.u .ocMd strong{color:rgba(255,255,255,.95)}
.ocMsg.u .ocMd code{background:rgba(255,255,255,.18);border:none;color:rgba(255,255,255,.9)}

/* Markdown */
.ocMd{line-height:1.75}
.ocMd p{margin:8px 0}.ocMd p:first-child{margin-top:0}.ocMd p:last-child{margin-bottom:0}
.ocMd h1,.ocMd h2,.ocMd h3{margin:20px 0 8px;color:var(--t1);font-weight:500}
.ocMd h1{font-size:22px}.ocMd h2{font-size:18px}.ocMd h3{font-size:15px}
.ocMd ul,.ocMd ol{margin:6px 0;padding-left:24px}.ocMd li{margin:3px 0}
.ocMd strong{color:var(--t1);font-weight:600}
.ocMd a{color:var(--pT);text-decoration:none}.ocMd a:hover{text-decoration:underline}
.ocMd code{background:var(--bgW);border:1px solid var(--bd);border-radius:6px;padding:2px 6px;font-family:var(--fm);font-size:12.5px;color:var(--pT)}
.ocMd pre{margin:10px 0;border-radius:var(--r);overflow:hidden;border:1px solid var(--bd)}
.ocMd pre code{display:block;padding:14px 18px;background:var(--bgS);border:none;font-size:12.5px;line-height:1.6;overflow-x:auto;color:var(--t1)}
.ocMd blockquote{border-left:2px solid var(--bd);padding-left:14px;color:var(--t2);margin:8px 0}
.ocMd hr{border:none;border-top:1px solid var(--bd);margin:12px 0}
.ocMd table{border-collapse:collapse;margin:10px 0;width:100%}.ocMd th,.ocMd td{border:1px solid var(--bd);padding:6px 10px;text-align:left;font-size:13px}.ocMd th{background:var(--bgW);font-weight:500}
.ocCH{display:flex;justify-content:space-between;align-items:center;padding:6px 14px;background:var(--bgW);font-size:11px;color:var(--t3);border-bottom:1px solid var(--bd2)}
.ocCC{cursor:pointer;color:var(--t3);font-size:11px;padding:3px 8px;border-radius:6px;background:none;border:none;font-family:var(--ff)}.ocCC:hover{color:var(--t1);background:var(--bgW)}

/* Tool calls */
.ocTk{margin:8px 0;border:1px solid var(--bd);border-radius:var(--r);background:var(--bgS);overflow:hidden;font-size:13px;border-left:3px solid var(--p)}
.ocTk.err{border-left-color:var(--er)}
.ocTkH{display:flex;align-items:center;padding:8px 12px;cursor:pointer;gap:8px;font-size:12px}.ocTkH:hover{background:var(--bgW)}
.ocTkN{font:500 12px/1 var(--fm);color:var(--pT)}
.ocTkDt{color:var(--t3);font-size:12px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ocTkB{padding:10px 14px;border-top:1px solid var(--bd2);max-height:200px;overflow-y:auto;font:12px/1.6 var(--fm);white-space:pre-wrap;word-break:break-all;color:var(--t2);display:none}
.ocTk.open .ocTkB{display:block}

/* Input */
.ocIA{border-top:1px solid var(--bd2);padding:16px 24px;background:var(--bgS);flex-shrink:0}
.ocIW{max-width:720px;margin:0 auto;display:flex;gap:10px;align-items:flex-end}
.ocIn{flex:1;resize:none;border:1px solid var(--bd);border-radius:var(--r);padding:12px 18px;background:var(--in);color:var(--t1);font:14.5px/1.5 var(--ff);min-height:48px;max-height:160px;outline:none;transition:all .2s;box-shadow:none}
.ocIn:focus{border-color:var(--p);box-shadow:0 0 0 4px rgba(59,130,246,.1)}.ocIn::placeholder{color:var(--t3)}
.ocSn{width:40px;height:40px;border-radius:var(--r);background:var(--p);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;transition:all .2s;box-shadow:0 2px 10px rgba(59,130,246,.25)}
.ocSn:hover{background:var(--pH);transform:translateY(-2px);box-shadow:0 6px 20px rgba(59,130,246,.35)}.ocSn:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none}

/* Status */
.ocSt{display:flex;align-items:center;padding:2px 20px;background:var(--bgS);border-top:1px solid var(--bd3);font-size:11px;color:var(--t3);gap:14px;flex-shrink:0;min-height:24px}
.ocSd{width:5px;height:5px;border-radius:50%;display:inline-block;margin-right:3px}.ocSd.on{background:var(--ok)}.ocSd.off{background:var(--err)}
#ocStat{display:inline-flex;gap:10px;margin-left:10px;font-size:11px;color:var(--t3)}
#ocStat>span{display:inline-flex;align-items:center;gap:2px;cursor:default}
#ocStat span[id$="V"]{font-weight:500;color:var(--t2)}
#ocStat:hover{opacity:.8}
#ocStatCache{color:var(--ac);font-weight:500}

.ocCtxR{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--bd3);font-size:13px}
.ocCtxR:last-child{border-bottom:none}
.ocCtxL{color:var(--t2)}
.ocCtxV{font-weight:600;color:var(--t1);font-family:var(--mono)}
.ocCtxS{margin-top:10px;padding-top:10px;border-top:1px solid var(--bd3)}
.ocCtxSt{font:500 11px/1 var(--sans);letter-spacing:.04em;text-transform:uppercase;color:var(--t3);margin-bottom:6px}
.ocCtxBr{display:flex;justify-content:space-between;padding:3px 0;font-size:12px}
.ocCtxBr span:first-child{color:var(--t3)}
.ocCtxBr span:last-child{color:var(--t1);font-weight:500}

.ocSetSrch{margin-bottom:10px}
.ocPl{display:flex;flex-direction:column;gap:4px;max-height:50vh;overflow-y:auto}
.ocPi{display:flex;align-items:center;padding:8px 10px;border:1px solid var(--bd3);border-radius:var(--r);gap:8px;transition:all .1s}
.ocPi:hover{border-color:var(--bd2);background:var(--bgW)}
.ocPiN{flex:1;font-size:13px;font-weight:500}
.ocPiSub{font-size:10px;color:var(--t3);display:block;margin-top:1px}
.ocPiB{font-size:10px;padding:1px 7px;border-radius:9px;background:var(--ac);color:#fff}
	.ocPiBo{background:var(--bgW);color:var(--t3)}
	.ocPi.ocPiHide{display:none}
	.ocCu{display:inline-block;width:2px;height:1em;background:var(--ac);animation:ocbl 1s infinite;vertical-align:text-bottom;margin-left:1px}
@keyframes ocbl{0%,50%{opacity:1}51%,100%{opacity:0}}

/* Modal */
.ocMo{display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:10000;align-items:center;justify-content:center}
.ocMo.open{display:flex}
.ocMoB{background:var(--bgS);border:1px solid var(--bd2);border-radius:12px;width:680px;max-height:85vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,.12)}
.ocMoH{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--bd3)}
.ocMoH h3{font-size:14px;font-weight:600}
.ocMoC{background:none;border:none;color:var(--t3);cursor:pointer;font-size:20px;padding:4px;line-height:1}.ocMoC:hover{color:var(--t1)}
.ocMoBd{padding:18px}
.ocFg{margin-bottom:12px}.ocFg label{display:block;font-size:12px;font-weight:500;margin-bottom:4px;color:var(--t2)}
.ocFi{width:100%;padding:7px 10px;border:1px solid var(--bd2);border-radius:var(--rS);background:var(--input);color:var(--t1);font:13px/1.5 var(--sans);outline:none}.ocFi:focus{border-color:var(--ac)}
.ocPl{display:grid;grid-template-columns:1fr 1fr;gap:4px}
.ocPi{display:flex;align-items:center;padding:8px 10px;border:1px solid var(--bd3);border-radius:var(--r);gap:8px;transition:all .1s}
.ocPi:hover{border-color:var(--bd2);background:var(--bgW)}
.ocPiN{flex:1;font-size:13px;font-weight:500}
.ocPiB{font-size:10px;padding:1px 7px;border-radius:9px;background:var(--ac);color:#fff}
.ocPiBo{background:var(--bgW);color:var(--t3)}

/* File tree panel */
.ocFt{position:fixed;right:0;top:var(--hh);height:calc(100vh - var(--hh));width:240px;border-left:1px solid var(--bd2);background:var(--bgW);display:flex;flex-direction:column;flex-shrink:0;overflow:hidden;transform:translateX(100%);transition:transform .15s ease;z-index:100}
.ocFt.open{transform:translateX(0)}
.ocFtH{padding:8px 12px;font:500 11px/1 var(--sans);letter-spacing:.04em;text-transform:uppercase;color:var(--t3);border-bottom:1px solid var(--bd3);display:flex;justify-content:space-between;align-items:center;flex-shrink:0}
.ocFtL{flex:1;overflow-y:auto;padding:2px 0;font-size:12px}
.ocFtI{display:flex;align-items:center;padding:3px 8px;cursor:pointer;color:var(--t2);gap:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ocFtI:hover{background:var(--bd3);color:var(--t1)}
.ocFtI.act{background:var(--bd3);color:var(--t1)}
.ocFtIc{width:14px;text-align:center;flex-shrink:0;font-size:11px;opacity:.6}
.ocFtIn{flex:1;overflow:hidden;text-overflow:ellipsis}
.ocFtS{font-size:10px;color:var(--t3);flex-shrink:0;margin-left:auto;padding-left:4px}

/* File viewer modal */
.ocFv{display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:10001;align-items:center;justify-content:center}
.ocFv.open{display:flex}
.ocFvB{background:var(--bgS);border:1px solid var(--bd2);border-radius:12px;width:80vw;max-width:900px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,.12)}
.ocFvH{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid var(--bd3);font-size:13px;font-weight:500}
.ocFvC{flex:1;overflow:auto;padding:0}
.ocFvC pre{margin:0;padding:14px;font:12.5px/1.5 var(--mono);white-space:pre-wrap;word-break:break-all;color:var(--t1)}

.ocThink{display:flex;align-items:center;gap:6px;padding:4px 0}
.ocThinkD{width:6px;height:6px;border-radius:50%;background:var(--ac);animation:ocBlink 1.4s infinite both}
.ocThinkD:nth-child(2){animation-delay:.2s}
.ocThinkD:nth-child(3){animation-delay:.4s}
@keyframes ocBlink{0%,80%,100%{opacity:.2}40%{opacity:1}}
.ocThinkT{color:var(--t3);font-size:12px;font-style:italic}

.ocStop{width:36px;height:36px;border-radius:var(--r);background:var(--err);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;transition:background .12s}.ocStop:hover{opacity:.85}
.ocTerm{background:#1a1a1a;color:#e0e0e0;font:12px/1.55 var(--mono);padding:10px 14px;border-radius:var(--r);max-height:250px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;margin:0}
.ocReason{border-left:2px solid var(--ac);padding:4px 10px;margin:4px 0;color:var(--t3);font-size:12.5px;font-style:italic;cursor:pointer;user-select:none}.ocReason:hover{background:var(--bgW)}
.ocReasonB{display:none;padding:6px 10px;font-size:12px;font-style:italic;color:var(--t3);white-space:pre-wrap;line-height:1.5}
.ocReason.open .ocReasonB{display:block}
.ocDiff{font:13px/1.6 var(--fm);border-radius:var(--r);overflow:hidden;border:1px solid var(--bd);max-height:320px;overflow-y:auto}
.ocDiffH{background:var(--bgW);padding:6px 14px;font-size:11px;color:var(--t3);border-bottom:1px solid var(--bd2);display:flex;justify-content:space-between}
.ocDiffL{padding:0 14px;white-space:pre;font-size:12px}
.ocDiffL.add{background:rgba(34,197,94,.1);color:#15803d}[data-cs="dark"] .ocDiffL.add{background:rgba(74,222,128,.12);color:#86efac}
.ocDiffL.del{background:rgba(239,68,68,.08);color:#b91c1c}[data-cs="dark"] .ocDiffL.del{background:rgba(248,113,113,.1);color:#fca5a5}
.ocDiffL.ctx{color:var(--t3)}
.ocTokenW{font-size:10px;padding:2px 6px;border-radius:var(--rP);font-weight:500;margin-left:6px}
.ocTokenW.amber{background:rgba(245,158,11,.12);color:var(--wr)}
.ocTokenW.red{background:rgba(239,68,68,.1);color:var(--er)}
.ocSlashHint{position:absolute;bottom:100%;left:0;right:0;background:var(--bgS);border:1px solid var(--bd);border-radius:var(--r);box-shadow:0 8px 24px rgba(0,0,0,.08);max-height:220px;overflow-y:auto;display:none;font-size:13px;z-index:100;margin-bottom:8px}
.ocSlashHint.open{display:block}
.ocSHItem{padding:8px 14px;cursor:pointer;display:flex;gap:10px;align-items:center}.ocSHItem:hover{background:var(--bgW)}
.ocSHCmd{font-weight:600;color:var(--p);font-family:var(--fm);white-space:nowrap;font-size:12px}
.ocSHDesc{color:var(--t3);font-size:12px}
.ocToast{position:fixed;top:20px;right:20px;z-index:20000;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.ocToastI{padding:12px 18px;border-radius:var(--r);font-size:13px;color:#fff;pointer-events:auto;animation:ocToastIn .3s cubic-bezier(.16,1,.3,1);box-shadow:0 10px 32px rgba(0,0,0,.12);max-width:380px;display:flex;align-items:center;gap:10px}
.ocToastI.ok{background:var(--ok)}.ocToastI.err{background:var(--er)}.ocToastI.warn{background:var(--wr)}.ocToastI.info{background:var(--p)}
.ocToastI.hide{animation:ocToastOut .25s ease forwards}
@keyframes ocToastIn{from{opacity:0;transform:translateY(-8px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes ocToastOut{to{opacity:0;transform:translateX(12px)}}
.ocMsgAct{opacity:0;display:flex;gap:6px;position:absolute;top:-6px;right:0;transition:opacity .2s}
.ocMsg:hover .ocMsgAct{opacity:1}
.ocMsgActB{width:28px;height:28px;border:1px solid var(--bd);border-radius:var(--rS);background:var(--bgS);color:var(--t3);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;transition:all .15s}
.ocMsgActB:hover{background:var(--bgW);color:var(--t1)}
.ocMsg{position:relative}
.ocMsgTs{font-size:11px;color:var(--t4);display:none;position:absolute;bottom:-4px;left:40px}
.ocMsg:hover .ocMsgTs{display:block}
.ocLoad{display:flex;align-items:center;justify-content:center;padding:48px;gap:8px;color:var(--t3);font-size:13px}
.ocLoadD{width:6px;height:6px;border-radius:50%;background:var(--p);animation:ocBlink 1.4s infinite both}
.ocLoadD:nth-child(2){animation-delay:.2s}.ocLoadD:nth-child(3){animation-delay:.4s}
.ocFtSrch{padding:6px 12px;border-bottom:1px solid var(--bd2)}
.ocFtSrch input{width:100%;border:1px solid var(--bd);border-radius:var(--rP);padding:5px 12px;font-size:12px;background:var(--in);color:var(--t1);outline:none;transition:border-color .2s}
.ocFtSrch input:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.ocChg{position:fixed;bottom:0;right:0;width:380px;max-height:50vh;background:var(--bgS);border:1px solid var(--bd);border-radius:var(--r) var(--r) 0 0;box-shadow:0 -8px 32px rgba(0,0,0,.08);z-index:9999;display:none;flex-direction:column}
.ocChg.open{display:flex}
.ocChgH{padding:14px 18px;border-bottom:1px solid var(--bd2);display:flex;justify-content:space-between;align-items:center;font-weight:500;font-size:14px}
.ocChgL{flex:1;overflow-y:auto;padding:6px 0}
.ocChgI{padding:8px 16px;display:flex;justify-content:space-between;align-items:center;font-size:13px;border-bottom:1px solid var(--bd2)}
.ocChgI:hover{background:var(--bgW)}
.ocChgBtn{font-size:11px;padding:4px 10px;border:1px solid var(--bd);border-radius:var(--rP);background:var(--bgS);color:var(--t1);cursor:pointer;transition:all .15s}
.ocChgBtn:hover{background:var(--bgW);border-color:var(--t4)}
.ocAtMention{background:var(--bgW);border:1px solid var(--bd);border-radius:var(--r);box-shadow:0 8px 24px rgba(0,0,0,.08);max-height:220px;overflow-y:auto;display:none;font-size:13px;z-index:100;position:absolute;bottom:100%;left:0;right:0;margin-bottom:8px}
.ocAtMention.open{display:block}
.ocAtItem{padding:6px 12px;cursor:pointer;display:flex;gap:6px;align-items:center;border-radius:0}.ocAtItem:hover{background:var(--bgW)}
.ocAtItem span:first-child{color:var(--p);font-family:var(--fm);font-weight:500}
.ocFaqI{border:1px solid var(--bd2);border-radius:var(--r);margin-bottom:8px;overflow:hidden}
.ocFaqQ{padding:12px 16px;cursor:pointer;font-weight:400;font-size:14px;display:flex;justify-content:space-between;align-items:center;gap:10px;background:var(--bgW);user-select:none}
.ocFaqQ:hover{background:var(--bgW)}
.ocFaqQ::after{content:'\25BC';font-size:10px;color:var(--t4);transition:transform .2s}
.ocFaqI.open .ocFaqQ::after{transform:rotate(180deg)}
.ocFaqA{padding:0 16px;max-height:0;overflow:hidden;transition:max-height .25s ease,padding .25s ease;font-size:13.5px;color:var(--t2);line-height:1.75}
.ocFaqI.open .ocFaqA{max-height:500px;padding:12px 16px}

/* ===== STATUS BAR ===== */
.ocSt{display:flex;align-items:center;padding:4px 24px;background:var(--bgS);border-top:1px solid var(--bd2);font-size:11px;color:var(--t3);gap:16px;flex-shrink:0;min-height:28px}
.ocSd{width:6px;height:6px;border-radius:50%;display:inline-block;margin-right:4px}.ocSd.on{background:var(--ok)}.ocSd.off{background:var(--er)}
#ocStat{display:inline-flex;gap:12px;margin-left:12px;font-size:11px;color:var(--t3)}
#ocStat>span{display:inline-flex;align-items:center;gap:3px;cursor:default;font-weight:500}
#ocStat span[id$="V"]{font-weight:500;color:var(--t2)}
#ocStat:hover{opacity:.8}
#ocStatCache{color:var(--p);font-weight:500}

/* ===== THINKING ===== */
.ocThink{display:flex;align-items:center;gap:8px;padding:6px 0}
.ocThinkD{width:7px;height:7px;border-radius:50%;background:var(--p);animation:ocBlink 1.4s infinite both}
.ocThinkD:nth-child(2){animation-delay:.2s}
.ocThinkD:nth-child(3){animation-delay:.4s}
@keyframes ocBlink{0%,80%,100%{opacity:.2}40%{opacity:1}}
.ocThinkT{color:var(--t3);font-size:13px;font-style:italic}

/* ===== CURSOR BLINK ===== */
.ocCu{display:inline-block;width:2px;height:1.1em;background:var(--p);animation:ocbl 1s infinite;vertical-align:text-bottom;margin-left:2px;border-radius:1px}
@keyframes ocbl{0%,50%{opacity:1}51%,100%{opacity:0}}

/* ===== CONTEXT DETAILS ===== */
.ocCtxR{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bd2);font-size:13px}
.ocCtxR:last-child{border-bottom:none}
.ocCtxL{color:var(--t2);font-weight:400}
.ocCtxV{font-weight:500;color:var(--t1);font-family:var(--fm)}
.ocCtxS{margin-top:12px;padding-top:12px;border-top:1px solid var(--bd2)}
.ocCtxSt{font:500 11px/1 var(--ff);letter-spacing:.06em;text-transform:uppercase;color:var(--t3);margin-bottom:8px}
.ocCtxBr{display:flex;justify-content:space-between;padding:4px 0;font-size:12px}
.ocCtxBr span:first-child{color:var(--t3)}
.ocCtxBr span:last-child{color:var(--t1);font-weight:500}

/* ===== MODAL ===== */
.ocMo{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:10000;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.ocMo.open{display:flex}
.ocMoB{background:var(--bgS);border:1px solid var(--bd2);border-radius:16px;width:680px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.ocMoH{display:flex;align-items:center;justify-content:space-between;padding:20px;border-bottom:1px solid var(--bd2)}
.ocMoH h3{font-size:18px;font-weight:500;color:var(--t1);letter-spacing:-.01em}
.ocMoC{background:none;border:none;color:var(--t3);cursor:pointer;font-size:22px;padding:4px;line-height:1;transition:color .15s}.ocMoC:hover{color:var(--t1)}
.ocMoBd{padding:20px}

/* ===== FORM ===== */
.ocFg{margin-bottom:16px}.ocFg label{display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--t2);letter-spacing:-.01em}
.ocFi{width:100%;padding:9px 12px;border:1px solid var(--bd);border-radius:var(--rS);background:var(--in);color:var(--t1);font:14px/1.5 var(--ff);outline:none;transition:all .2s}.ocFi:focus{border-color:var(--p);box-shadow:0 0 0 4px rgba(59,130,246,.1)}

/* ===== PROVIDER LIST ===== */
.ocPl{display:grid;grid-template-columns:1fr 1fr;gap:8px;max-height:50vh;overflow-y:auto;padding-right:4px}
.ocPi{display:flex;align-items:center;padding:12px;border:1px solid var(--bd3);border-radius:var(--rS);gap:10px;transition:all .2s;cursor:pointer}
.ocPi:hover{border-color:var(--p);background:var(--bgW);box-shadow:0 2px 8px rgba(59,130,246,.08)}
.ocPiN{flex:1;font-size:13px;font-weight:500}
.ocPiSub{font-size:11px;color:var(--t3);display:block;margin-top:2px}
.ocPiB{font-size:10px;padding:3px 8px;border-radius:var(--rP);background:var(--p);color:#fff;font-weight:500}
.ocPiBo{background:var(--bgW);color:var(--t3);border:1px solid var(--bd)}
.ocPi.ocPiHide{display:none}

/* ===== FILE TREE PANEL ===== */
.ocFt{position:fixed;right:0;top:var(--hh);height:calc(100vh - var(--hh));width:260px;border-left:1px solid var(--bd2);background:var(--bgS);display:flex;flex-direction:column;flex-shrink:0;overflow:hidden;transform:translateX(100%);transition:transform .25s cubic-bezier(.4,0,.2,1);z-index:100}
.ocFt.open{transform:translateX(0)}
.ocFtH{padding:10px 16px;font:500 12px/1 var(--ff);letter-spacing:.05em;text-transform:uppercase;color:var(--t3);border-bottom:1px solid var(--bd2);display:flex;justify-content:space-between;align-items:center;flex-shrink:0}
.ocFtL{flex:1;overflow-y:auto;padding:6px 0;font-size:13px}
.ocFtI{display:flex;align-items:center;padding:5px 12px;cursor:pointer;color:var(--t2);gap:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-radius:0;transition:all .1s}.ocFtI:hover{background:var(--bgW);color:var(--t1)}
.ocFtI.act{background:var(--bgW);color:var(--p)}.ocFtI.act .ocFtIc{color:var(--p)}
.ocFtIc{width:14px;text-align:center;flex-shrink:0;font-size:12px;opacity:.6;transition:color .1s}
.ocFtIn{flex:1;overflow:hidden;text-overflow:ellipsis;font-size:13px}
.ocFtS{font-size:11px;color:var(--t3);flex-shrink:0;margin-left:auto;padding-left:6px}

/* ===== FILE VIEWER ===== */
.ocFv{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:10001;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.ocFv.open{display:flex}
.ocFvB{background:var(--bgS);border:1px solid var(--bd2);border-radius:16px;width:80vw;max-width:900px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.ocFvH{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--bd2);font-size:14px;font-weight:500}
.ocFvC{flex:1;overflow:auto;padding:0}
.ocFvC pre{margin:0;padding:18px;font:13px/1.65 var(--fm);white-space:pre-wrap;word-break:break-all;color:var(--t1)}

/* ===== FILE TREE SUB ===== */
.ocFtSub{padding-left:16px}

/* ===== ACCESSIBILITY ===== */
.ocIb:focus-visible,.ocB:focus-visible,.ocSn:focus-visible,.ocStop:focus-visible,.ocMoC:focus-visible,.ocMsgActB:focus-visible{outline:2px solid var(--p);outline-offset:2px;border-radius:var(--rS)}
.ocFi:focus-visible,.ocIn:focus-visible{outline:none;border-color:var(--p);box-shadow:0 0 0 4px rgba(59,130,246,.1)}
.ocFtI:focus-visible,.ocSi:focus-visible,.ocSHItem:focus-visible,.ocAtItem:focus-visible,.ocPi:focus-visible{outline:2px solid var(--p);outline-offset:-2px;border-radius:var(--rS)}
@media(prefers-reduced-motion:reduce){
*{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}
.ocFt{transition:none}
}
</style>
</head>
<body>

<div class="ocToast" id="ocToast"></div>

<div id="oc">
<div class="ocH">
	<div class="ocHl">
		<button class="ocIb" id="ocTogSb" title="Toggle sidebar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
		<select class="ocSel" id="ocProject" title="Switch project" style="margin-left:4px;max-width:200px"><option value="">Loading projects...</option></select>
	</div>
	<div class="ocHc">
		<select class="ocSel" id="ocMode" title="Mode"><option value="build">Build</option><option value="plan">Plan</option></select>
		<select class="ocSel" id="ocModel" title="Model"><option value="">Select model</option></select>
	</div>
	<div class="ocHr">
		<button class="ocB ocBs" id="ocProjBtn" title="Projects"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg> <span class="ocBtnTxt">Projects</span></button>
		<button class="ocB ocBs" id="ocSetBtn" title="Settings"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg> <span class="ocBtnTxt">Settings</span></button>
		<button class="ocB ocBs" id="ocFtBtn" title="Files"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg> <span class="ocBtnTxt">Files</span></button>
		<button class="ocB ocBs" id="ocChgBtn" title="Changes"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg> <span class="ocBtnTxt">Changes</span></button>
		<?php if(!empty($back_url)): ?><a href="<?php echo $back_url; ?>" class="ocB ocBs" title="Back"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg> <span class="ocBtnTxt">Back</span></a><?php endif; ?>
		<button class="ocB ocBs" id="ocHlBtn" title="Help"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> <span class="ocBtnTxt">Help</span></button>
		<button class="ocIb" id="ocThBtn" title="Toggle theme">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
			  <circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/>
			  <line x1="19" y1="12" x2="22" y2="12"/><line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
			  <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/>
			</svg>
		</button>
	</div>
</div>
<div class="ocBd">
	<div class="ocSb" id="ocSb">
		<div class="ocSbH"><span>History</span><button class="ocB ocBs" id="ocNew">+ New Session</button></div>
		<div class="ocSbL" id="ocSL"></div>
	</div>
	<div class="ocMn">
		<div class="ocC" id="ocChat"><div class="ocCi" id="ocCI"></div></div>
		<div class="ocIA">
			<div class="ocIW" style="position:relative">
				<div style="position:relative;flex:1;display:flex">
					<textarea class="ocIn" id="ocIn" placeholder="Ask me to code, debug, explain, or refactor..." rows="1" style="padding-right:28px"></textarea>
					<button class="ocSn" id="ocAttBtn" title="Attach files" style="position:absolute;right:4px;top:50%;transform:translateY(-50%);background:transparent;border:none;color:var(--t1);opacity:0.5;font-size:18px;width:28px;height:28px">+</button>
				</div>
				<input type="file" id="ocFile" multiple style="display:none">
				<button class="ocSn" id="ocSend"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9" fill="none"/></svg></button>
				<button class="ocStop" id="ocStopBtn" style="display:none" title="Stop (Esc)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="6" width="12" height="12" rx="2"/></svg></button>
				<div class="ocSlashHint" id="ocSlash"></div>
			</div>
		</div>
		<div class="ocSt">
			<span><span class="ocSd" id="ocSD"></span><span id="ocST">Ready</span></span>
			<span id="ocStat" style="display:none;cursor:pointer">
				<span id="ocStatCtx" title="Context window"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg><span id="ocStatCtxV">0</span></span>
				<span id="ocStatIn" title="Input tokens"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg><span id="ocStatInV">0</span></span>
				<span id="ocStatCache" title="Cached tokens"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg><span id="ocStatCacheV">0</span></span>
				<span id="ocStatOut" title="Output tokens"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg><span id="ocStatOutV">0</span></span>
				<span id="ocStatTools" title="Tool calls"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg><span id="ocStatToolsV">0</span></span>
				<span id="ocStatIter" title="Iterations"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg><span id="ocStatIterV">0</span></span>
				<span id="ocStatCost" title="Cost"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>$<span id="ocStatCostV">0</span></span>
			</span>
			<span style="flex:1"></span>
			<span style="opacity:.6"><?php echo htmlspecialchars($softpath); ?></span>
		</div>
	</div>
</div>
</div>

<div class="ocChg" id="ocChg">
	<div class="ocChgH"><span>Changes</span><button class="ocMoC" id="ocChgC"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
	<div class="ocChgL" id="ocChgL"></div>
</div>

<div class="ocMo" id="ocSetMod">
<div class="ocMoB">
	<div class="ocMoH"><h3>Settings</h3><div style="display:flex;gap:6px;align-items:center"><button class="ocB ocBP ocBs" id="ocAddCon" style="font-size:11px">+ Add Connection</button><button class="ocMoC" id="ocSetC"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div></div>
	<div class="ocMoBd">
		<input type="text" class="ocFi" id="ocSetSearch" placeholder="Search providers..." style="margin-bottom:10px">
		<div class="ocPl" id="ocPL"></div>
	</div>
</div>
</div>

<div class="ocMo" id="ocConMod">
<div class="ocMoB" style="width:440px">
	<div class="ocMoH"><h3 id="ocConTitle">Add Connection</h3><button class="ocMoC" id="ocConC"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
	<div class="ocMoBd">
		<div class="ocFg"><label>Provider</label><select class="ocFi" id="ocProvSel" style="cursor:pointer"></select></div>
		<div class="ocFg" id="ocCustNameG" style="display:none"><label>Provider Name</label><input type="text" class="ocFi" id="ocCustName" placeholder="My Provider"></div>
		<div class="ocFg" id="ocBUG" style="display:none"><label>Base URL</label><input type="text" class="ocFi" id="ocBU" placeholder="https://api.example.com/v1"></div>
		<div class="ocFg" id="ocCustModelsG" style="display:none"><label>Models (comma-separated: id=name, id2=name2)</label><input type="text" class="ocFi" id="ocCustModels" placeholder="gpt-4o=GPT-4o, llama3=Llama 3"></div>
		<div class="ocFg"><label>API Key</label><input type="password" class="ocFi" id="ocAK" placeholder="Enter your API key (optional for some)"></div>
		<div style="display:flex;gap:8px;justify-content:flex-end"><button class="ocB" id="ocCan2">Cancel</button><button class="ocB" id="ocTst">Test</button><button class="ocB ocBP" id="ocSav">Connect</button></div>
	</div>
</div>
</div>

<div class="ocMo" id="ocCtxMod">
<div class="ocMoB" style="width:440px">
	<div class="ocMoH"><h3>Context Details</h3><button class="ocMoC" id="ocCtxC"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
	<div class="ocMoBd" id="ocCtxBd"></div>
</div>
</div>

<div class="ocMo" id="ocProjMod">
<div class="ocMoB" style="width:600px;max-height:80vh">
	<div class="ocMoH"><h3>Project Management</h3><button class="ocMoC" id="ocProjC"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
	<div class="ocMoBd">
		<div style="margin-bottom:16px">
			<div style="display:flex;gap:8px;margin-bottom:12px">
				<input type="text" class="ocFi" id="ocProjSearch" placeholder="Search projects..." style="flex:1">
				<button class="ocB ocBP" id="ocProjNewBtn" style="white-space:nowrap">+ New Project</button>
			</div>
		</div>
		<div id="ocProjList" style="max-height:400px;overflow-y:auto"></div>
		<div id="ocProjNewForm" style="display:none;margin-top:16px;padding-top:16px;border-top:1px solid var(--bd3)">
			<h4 style="font-size:14px;font-weight:500;margin-bottom:12px">Create New Project</h4>
			<div class="ocFg"><label>Project Name (optional)</label><input type="text" class="ocFi" id="ocProjName" placeholder="My Project"></div>
			<div class="ocFg"><label>Directory Path</label>
				<div style="display:flex;gap:0">
					<span style="background:var(--bgW);border:1px solid var(--bd2);border-right:none;border-radius:var(--rS) 0 0 var(--rS);padding:7px 10px;font-size:12px;color:var(--t3);white-space:nowrap"><?php echo htmlspecialchars($homedir); ?>/</span>
					<input type="text" class="ocFi" id="ocProjPath" placeholder="public_html/project" style="border-radius:0 var(--rS) var(--rS) 0">
				</div>
			</div>
			<div id="ocProjWpSection" style="display:none;margin-bottom:12px">
				<label style="font-size:12px;font-weight:400;color:var(--t2);display:block;margin-bottom:4px">Or select a WordPress installation:</label>
				<select class="ocFi" id="ocProjWpSel" style="cursor:pointer"><option value="">Select installation...</option></select>
			</div>
			<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
				<button class="ocB" id="ocProjCan">Cancel</button>
				<button class="ocB ocBP" id="ocProjCre">Create Project</button>
			</div>
		</div>
	</div>
</div>
</div>

<div class="ocFt" id="ocFt">
	<div class="ocFtH"><span>Files</span><button class="ocB ocBs" id="ocFtRef" style="font-size:10px;padding:0 6px;height:20px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg></button></div>
	<div class="ocFtSrch"><input type="text" id="ocFtSearch" placeholder="Search files..."></div><div class="ocFtL" id="ocFtL"></div>
</div>

<div class="ocFv" id="ocFv">
<div class="ocFvB">
	<div class="ocFvH"><span id="ocFvT">file.php</span><button class="ocMoC" id="ocFvC"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
	<div class="ocFvC" id="ocFvP"><pre id="ocFvPre"></pre></div>
</div>
</div>

<div class="ocMo" id="ocHelpMod">
<div class="ocMoB" style="width:600px;max-height:80vh">
	<div class="ocMoH"><h3>Help & FAQ</h3><button class="ocMoC" id="ocHelpC"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
	<div class="ocMoBd" id="ocHelpBd" style="max-height:calc(80vh - 60px);overflow-y:auto"></div>
</div>
</div>

<script>
(function(){
var A='<?php echo $api_base;?>',C='<?php echo $csrf;?>',SP='<?php echo addslashes($softpath);?>',HD='<?php echo addslashes($homedir);?>',PID='<?php echo addslashes($project_id);?>',pv=[],str=false,cS=null,aC=null,ctx='',selModel='',selMode='',lastStats={},modelCtx={},projects=[],wordpressInstalls=[],pendingFiles=[],_origTitle=document.title,_notifyPending=false;

marked.setOptions({breaks:true,gfm:true});
var R=new marked.Renderer();
R.code=function(code,lang){
	var l=lang||'',h='';
	try{h=hljs.highlightAuto(code).value}catch(e){h=code.replace(/</g,'&lt;')}
	return '<div class="ocCH"><span>'+l+'</span><button class="ocCC" onclick="window._cp(this)">Copy</button></div><pre><code class="hljs">'+h+'</code></pre>';
};
marked.use({renderer:R});
window._cp=function(el){
	var c=el.closest('pre').querySelector('code');
	navigator.clipboard.writeText(c.textContent);
	el.textContent='Copied!';
	setTimeout(function(){el.textContent='Copy'},1200);
};

function api(a,d,cb){
	var iP=!!d,u=A+'&ai_php_api='+a+'&csrf_token='+C,x=new XMLHttpRequest();
	x.open(iP?'POST':'GET',u,true);
	x.setRequestHeader('Accept','application/json');
	if(iP)x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	x.onload=function(){
		if(x.status===200){
			try{cb(null,JSON.parse(x.responseText))}
			catch(e){cb('Parse error')}
		}else{
			var em='HTTP '+x.status;
			try{var ej=JSON.parse(x.responseText);if(ej.data&&(ej.data.message||ej.data.error))em=ej.data.message||ej.data.error}catch(e){}
			cb(em);
		}
	};
	x.onerror=function(){cb('Network error')};
	x.send(iP?bp(d):null);
}

function bp(o){
	var p=[];
	for(var k in o){
		if(o.hasOwnProperty(k))p.push(encodeURIComponent(k)+'='+encodeURIComponent(o[k]));
	}
	return p.join('&');
}

function md(t){return t?DOMPurify.sanitize(marked.parse(t)):''}
function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML}

function st(t,c){
	document.getElementById('ocST').textContent=t;
	document.getElementById('ocSD').className='ocSd '+(c?'on':'off');
}

var _userSvg='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';

function rMsg(m){
	var ch=document.getElementById('ocCI');
	var d=document.createElement('div');
	d.className='ocMsg '+(m.role==='user'?'u':'a');
	var av=document.createElement('div');
	av.className='ocAv';
	av.innerHTML=m.role==='user'?_userSvg:'AI';
	var bd=document.createElement('div');
	bd.className='ocMb';
	var bb=document.createElement('div');
	bb.className='ocMbb';
	if(m.role==='user'){
		bb.textContent=m.content||'';
	}else if(m.content==='__thinking__'){
		bb.innerHTML='<div class="ocThink"><span class="ocThinkD"></span><span class="ocThinkD"></span><span class="ocThinkD"></span><span class="ocThinkT">Thinking...</span></div>';
		bb.dataset.thinking='1';
	}else{
		var pts=m.parts||[],tx='',ht=false;
		pts.forEach(function(p){
			if(p.type==='text'&&p.text){tx+=p.text;ht=true}
		});
		pts.forEach(function(p){
			if(p.type==='reasoning'&&p.text){
				var rd=document.createElement('div');
				rd.className='ocReason';
				rd.innerHTML='<span>Thoughts (click to expand)</span><div class="ocReasonB">'+esc(p.text)+'</div>';
				rd.addEventListener('click',function(){this.classList.toggle('open')});
				bb.appendChild(rd);
			}
		});
		if(ht)bb.innerHTML+='<div class="ocMd">'+md(tx)+'</div>';
		pts.forEach(function(p){
			if(p.type==='tool_use'){
				var t=document.createElement('div');
				t.className='ocTk';
				t.innerHTML='<div class="ocTkH" onclick="this.parentElement.classList.toggle(\'open\')">'
					+'<span class="ocTkN">'+esc(p.name||'tool')+'</span>'
					+'<span class="ocTkDt">'+esc(JSON.stringify(p.input||{}).substring(0,100))+'</span>'
					+'</div><div class="ocTkB">'+esc(JSON.stringify(p.input||{},null,2))+'</div>';
				bb.appendChild(t);
			}
		});
		if(!ht&&pts.length>0&&!pts.some(function(p){return p.type==='tool_use'})){
			if(typeof m.content==='string'&&m.content){
				bb.innerHTML='<div class="ocMd">'+md(m.content)+'</div>';
			}
		}
	}
	bd.appendChild(bb);
	d.appendChild(av);
	d.appendChild(bd);
	ch.appendChild(d);
	document.getElementById('ocChat').scrollTop=1e9;
	return bb;
}

function rMsgs(msgs){
	var ch=document.getElementById('ocCI');
	ch.innerHTML='';
	if(!msgs||!msgs.length){
		ch.innerHTML='<div class="ocE"><div class="ocEi"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3L20 7.5V16.5L12 21L4 16.5V7.5L12 3Z"/><path d="M12 12L20 7.5"/><path d="M12 12V21"/><path d="M12 12L4 7.5"/></svg></div>'
			+'<div class="ocEt">Code with AI</div>'
			+'<div class="ocEs">Select a model above, then ask me to code, debug, or refactor.<br>'
			+'I can read, write, edit files and run shell commands.</div></div>';
		rCtx();
		return;
	}
	rCtx();
	var totalIn=0,totalOut=0,totalCost=0,toolCalls=0,iters=0,ctxTokens=0,totalCached=0;
	msgs.forEach(function(m){
		if(m.role==='tool_result')return;
		rMsg(m);
		if(m.role==='assistant'){
			iters++;
			if(m.usage){
				totalIn+=(m.usage.prompt_tokens||0)+(m.usage.input_tokens||0);
				totalOut+=(m.usage.completion_tokens||0)+(m.usage.output_tokens||0);
				totalCached+=(m.usage.cached_tokens||0)+(m.usage.cache_read_input_tokens||0)+(m.usage.cache_creation_input_tokens||0);
				if(m.usage.prompt_tokens_details&&m.usage.prompt_tokens_details.cached_tokens)totalCached+=m.usage.prompt_tokens_details.cached_tokens;
				if(m.usage.cost)totalCost+=m.usage.cost;
				if(m.usage.cost_details&&m.usage.cost_details.upstream_inference_cost)totalCost+=m.usage.cost_details.upstream_inference_cost;
			}
			(m.parts||[]).forEach(function(p){if(p.type==='tool_use')toolCalls++});
		}
	});
	if(totalIn>0||iters>0){
		var totalChars=0;
		msgs.forEach(function(m){
			if(m.content&&typeof m.content==='string')totalChars+=m.content.length;
			(m.parts||[]).forEach(function(p){
				if(p.text)totalChars+=p.text.length;
				if(p.input)totalChars+=JSON.stringify(p.input).length;
			});
		});
		ctxTokens=Math.round(totalChars/4);
		updateStats({usage:{input_tokens:totalIn,output_tokens:totalOut,cached_tokens:totalCached},iterations:iters,context_tokens:ctxTokens,tool_calls:toolCalls,cost:totalCost});
	}else{
		document.getElementById('ocStat').style.display='none';
	}
}

function rCtx(){
	if(!ctx)return;
	var w=document.getElementById('ocCI');
	var ex=w.querySelector('.ocCtx');
	if(ex)return;
	var d=document.createElement('div');
	d.className='ocCtx';
	d.innerHTML='<div class="ocCtxI">'+ctx+'</div>';
	w.insertBefore(d,w.firstChild);
}

function abortStream(){
	if(cS){cS.abort();cS=null}
	str=false;
	document.getElementById('ocSend').style.display='flex';
	document.getElementById('ocStopBtn').style.display='none';
}

function loadC(id){
	abortStream();
	aC=id;
	updateURL(id);
	var u=A+'&ai_php_api=conversation&csrf_token='+C+'&conversation_id='+encodeURIComponent(id);
	var x=new XMLHttpRequest();
	x.open('GET',u,true);
	x.setRequestHeader('Accept','application/json');
	x.onload=function(){
		if(x.status===200){
			try{
				var d=JSON.parse(x.responseText);
				rMsgs(d.messages||[]);
				hlS(id);
			}catch(e){}
		}
	};
	x.send();
}

function loadSL(){
	api('conversations',null,function(e,d){
		if(e||!d)return;
		var ls=document.getElementById('ocSL');
		ls.innerHTML='';
		if(!d.length){
			ls.innerHTML='<div style="padding:20px;color:var(--t3);font-size:12px;text-align:center">No sessions yet</div>';
			return;
		}
		d.forEach(function(s){
			var it=document.createElement('div');
			it.className='ocSi'+(s.id===aC?' act':'');
			it.dataset.id=s.id;
			var ts='';
			if(s.updated_at){
				var dt=new Date(s.updated_at*1000);
				ts=dt.toLocaleDateString()===new Date().toLocaleDateString()
					?dt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})
					:dt.toLocaleDateString([],{month:'short',day:'numeric'});
			}
			it.innerHTML='<span class="ocSiI">'+(s.mode==='plan'?'&#9679;':'&#128172;')+'</span>'
				+'<span class="ocSiT">'+esc(s.title||'Untitled')+'</span>'
				+(s.message_count?'<span class="ocSiB">'+s.message_count+'</span>':'')
				+'<button class="ocSiD" data-d="1" title="Delete"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';

			it.addEventListener('click',function(ev){

				let btn = ev.target.closest('button');

				if(btn && btn.dataset && btn.dataset.d){
					ev.stopPropagation();
					if(confirm('Delete this session?')){
						if(aC===s.id) abortStream();
						api('delete_conversation',{conversation_id:s.id},function(){
							loadSL();
							if(aC===s.id){aC=null;rMsgs([])}
						});
					}
					return;
				}
				var mv=document.getElementById('ocModel').value;
				var pp=mv.split('/');
				api('start',{provider:pp[0],model:pp.slice(1).join('/'),mode:document.getElementById('ocMode').value},function(){
					aC=s.id;
					api('switch_conversation',{conversation_id:s.id},function(){
						loadC(s.id);
					});
				});
			});
			ls.appendChild(it);
		});
	});
}

function hlS(id){
	document.querySelectorAll('.ocSi').forEach(function(el){
		el.classList.toggle('act',el.dataset.id===id);
	});
}

function updateStats(d){
	if(!d)return;
	lastStats=d;
	var u=d.usage||{};
	document.getElementById('ocStat').style.display='inline-flex';
	document.getElementById('ocStatCtxV').textContent=fmtTok(d.context_tokens||0);
	document.getElementById('ocStatInV').textContent=fmtTok(u.input_tokens||0);
	var cached=u.cached_tokens||0;
	document.getElementById('ocStatCacheV').textContent=cached>0?fmtTok(cached):'0';
	document.getElementById('ocStatOutV').textContent=fmtTok(u.output_tokens||0);
	document.getElementById('ocStatToolsV').textContent=d.tool_calls||0;
	document.getElementById('ocStatIterV').textContent=d.iterations||0;
	var cost=d.cost||0;
	var mv=document.getElementById('ocModel').value;
	var isFreeModel=mv&&mv.indexOf('opencode_zen/')===0;
	var costEl=document.getElementById('ocStatCost');
	if(isFreeModel||cost<=0){
		costEl.style.display='none';
	}else{
		costEl.style.display='inline-flex';
		document.getElementById('ocStatCostV').textContent=cost<0.01&&cost>0?cost.toFixed(6):cost.toFixed(4);
	}
	var ctxEl=document.getElementById('ocStatCtx');
	var old=ctxEl.querySelector('.ocTokenW');
	if(old)old.remove();
	var ctxTok=d.context_tokens||0;
	if(mv&&ctxTok>0){
		var modelId=mv.split('/').slice(1).join('/');
		var limit=modelCtx[modelId]||0;
		if(limit>0){
			var pct=ctxTok/limit;
			if(pct>0.95){
				var w=document.createElement('span');w.className='ocTokenW red';w.textContent=Math.round(pct*100)+'%';
				ctxEl.appendChild(w);
			}else if(pct>0.8){
				var w=document.createElement('span');w.className='ocTokenW amber';w.textContent=Math.round(pct*100)+'%';
				ctxEl.appendChild(w);
			}
		}
	}
}

function newS(){
	abortStream();
	api('new_session',{},function(e,r){
		if(!e&&r&&r.id){
			aC=r.id;
			updateURL(aC);
			rMsgs([]);
			loadSL();
			setTimeout(function(){hlS(aC)},100);
			var mv=document.getElementById('ocModel').value;
			var pp=mv.split('/');
			api('start',{provider:pp[0],model:pp.slice(1).join('/'),mode:document.getElementById('ocMode').value},function(){});
		}
	});
}

function send(){
	var inp=document.getElementById('ocIn');
	var text=inp.value.trim();
	if(!text||str)return;

	if(text.charAt(0)==='/'){
		inp.value='';
		inp.style.height='40px';
		var cmd=text.replace(/\s+/g,' ').split(' ');
		var c=cmd[0].toLowerCase();
		if(c==='/clear'){api('clear',{},function(){rMsgs([]);st('Cleared',true)});return}
		if(c==='/export'){exportChat();return}
		if(c==='/plan'){api('set_mode',{mode:'plan'},function(){document.getElementById('ocMode').value='plan';st('Plan mode',true)});return}
		if(c==='/build'){api('set_mode',{mode:'build'},function(){document.getElementById('ocMode').value='build';st('Build mode',true)});return}
		if(c==='/help'){
			var bb=rMsg({role:'asst',content:'',parts:[]});
			bb.innerHTML='<div class="ocMd">'+md('**Keyboard Shortcuts:**\n- `Escape` — Stop streaming\n- `Ctrl+Shift+N` — New session\n- `Ctrl+Shift+P` — Plan mode\n- `Ctrl+,` — Settings\n\n**Slash Commands:**\n- `/plan` — Switch to Plan mode\n- `/build` — Switch to Build mode\n- `/clear` — Clear conversation\n- `/export` — Export as Markdown\n- `/help` — Show this help')+'</div>';
			return;
		}
		var bb=rMsg({role:'asst',content:'',parts:[]});
		bb.innerHTML='<div style="color:var(--err);padding:6px">Unknown command: '+esc(c)+'. Try /help</div>';
		return;
	}

	inp.value='';
	inp.style.height='40px';
	if(typeof Notification!=='undefined'&&Notification.permission==='default')Notification.requestPermission();
	rMsg({role:'user',content:text});
	str=true;
	document.getElementById('ocSend').style.display='none';
	document.getElementById('ocStopBtn').style.display='flex';
	st('Thinking...',true);
	var thinkBubble=rMsg({role:'asst',content:'__thinking__',parts:[]});

	var doSend=function(){
		var bb=null,full='',firstContent=false,reasonDiv=null,reasonText='';
		var sendText=text;
		if(pendingFiles.length>0){
			sendText+='\n\n[Attached files:]';
			pendingFiles.forEach(function(f){
				sendText+='\n\n--- '+f.name+' ---\n'+f.content;
			});
		}
		var params='content='+encodeURIComponent(sendText)+'&csrf_token='+C;
		if(aC)params+='&conversation_id='+encodeURIComponent(aC);
		if(pendingFiles.length>0)params+='&attachments='+encodeURIComponent(JSON.stringify(pendingFiles));
		pendingFiles=[];
		document.getElementById('ocFile').value='';
		var xhr=new XMLHttpRequest();
		xhr.open('POST',A+'&ai_chat_stream=1',true);
		xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		cS=xhr;
		var buf='';
		var lineBuf='';
		xhr.onprogress=function(){
			var nt=xhr.responseText.substring(buf.length);
			buf=xhr.responseText;
			lineBuf+=nt;
			var lines=lineBuf.split('\n');
			lineBuf=lines.pop();
			lines.forEach(function(line){
				if(line.indexOf('data: ')!==0)return;
				try{
					var d=JSON.parse(line.substring(6));
					var ev=d._event||'';
				if(!firstContent&&(ev==='text-delta'||ev==='tool-call'||ev==='tool-result'||ev==='reasoning-delta')){
						firstContent=true;
						if(thinkBubble){
							var msgEl=thinkBubble.closest('.ocMsg');
							if(msgEl&&msgEl.parentNode)msgEl.parentNode.removeChild(msgEl);
							thinkBubble=null;
						}
					}
					if(ev==='reasoning-delta'&&d.text){
						reasonText+=d.text;
						if(!bb){bb=rMsg({role:'asst',content:'',parts:[]})}
						if(!reasonDiv){
							reasonDiv=document.createElement('div');
							reasonDiv.className='ocReason open';
							reasonDiv.innerHTML='<span>Thinking...</span><div class="ocReasonB"></div>';
							reasonDiv.addEventListener('click',function(){this.classList.toggle('open')});
							bb.insertBefore(reasonDiv,bb.firstChild);
						}
						reasonDiv.querySelector('.ocReasonB').textContent=reasonText;
						document.getElementById('ocChat').scrollTop=1e9;
					}
					if(ev==='text-delta'&&d.text){
						if(reasonDiv){reasonDiv.classList.remove('open');reasonDiv.querySelector('span').textContent='Thoughts (click to expand)'}
						full+=d.text;
						if(!bb){bb=rMsg({role:'asst',content:'',parts:[]})}
						var me=bb.querySelector('.ocMd');
						if(!me){
							bb.innerHTML='<div class="ocMd">'+md(full)+'<span class="ocCu"></span></div>';
							me=bb.querySelector('.ocMd');
						}
						else me.innerHTML=md(full)+'<span class="ocCu"></span>';
						document.getElementById('ocChat').scrollTop=1e9;
					}
					if(ev==='tool-call'){
						var td=document.createElement('div');
						td.className='ocTk';
						var inp2=JSON.stringify(d.input||{});
						var cmdPreview=d.name==='bash'?('$ '+(d.input.command||'')).substring(0,100):inp2.substring(0,100);
						td.innerHTML='<div class="ocTkH" onclick="this.parentElement.classList.toggle(\'open\')">'
							+'<span class="ocTkN">'+esc(d.name||'tool')+'</span>'
							+'<span class="ocTkDt">'+esc(cmdPreview)+'</span>'
							+'<span style="color:var(--ac);font-size:10px;margin-left:auto">running...</span>'
							+'</div><div class="ocTkB">'+esc(JSON.stringify(d.input||{},null,2))+'</div>';
						if(!bb){bb=rMsg({role:'asst',content:'',parts:[]})}
						bb.appendChild(td);
						document.getElementById('ocChat').scrollTop=1e9;
						td._toolId=d.id;
						td._toolName=d.name;
					}
					if(ev==='tool-result'){
						var found=null;
						if(bb){
							var kids=bb.querySelectorAll('.ocTk');
							for(var k=0;k<kids.length;k++){
								if(kids[k]._toolId===d.id){found=kids[k];break}
							}
						}
						if(found){
							var isErr=!(!d.is_error);
							if(isErr)found.classList.add('err');
							var summary=String(d.output||'').substring(0,100);
							found.querySelector('.ocTkDt').textContent=summary;
							var runningSpan=found.querySelector('span[style*="running"]');
							if(runningSpan)runningSpan.textContent=isErr?'error':'done';
							var body=found.querySelector('.ocTkB');
							if(body){
								if(d.diff){
									body.innerHTML=renderDiff(d.diff);
								}else if(found._toolName==='bash'){
									body.innerHTML='<div class="ocTerm">'+esc(d.output||'')+'</div>';
								}else{
									body.textContent=d.output||'';
								}
							}
						}else{
							var td2=document.createElement('div');
							td2.className='ocTk'+(d.is_error?' err':'');
							var outputHtml='';
							if(d.diff){
								outputHtml=renderDiff(d.diff);
							}else if(d.name==='bash'){
								outputHtml='<div class="ocTerm">'+esc(d.output||'')+'</div>';
							}else{
								outputHtml=esc(d.output||'');
							}
							td2.innerHTML='<div class="ocTkH" onclick="this.parentElement.classList.toggle(\'open\')">'
								+'<span class="ocTkN">'+esc(d.name||'tool')+'</span>'
								+'<span class="ocTkDt">'+esc(String(d.output||'').substring(0,100))+'</span>'
								+'</div><div class="ocTkB">'+outputHtml+'</div>';
							if(!bb){bb=rMsg({role:'asst',content:'',parts:[]})}
							bb.appendChild(td2);
						}
						document.getElementById('ocChat').scrollTop=1e9;
					}
					if(ev==='done'){
						var statusEl=document.getElementById('ocST');
						statusEl.textContent='Ready';
						updateStats(d);
						if(d.conversation_id){aC=d.conversation_id;updateURL(aC)}
						_notifyOnComplete('AI Response Ready','Chat completed');
					}
					if(ev==='usage'){
						updateStats(d);
					}
					if(ev==='iteration-start'){
						bb=null;
						full='';
						reasonDiv=null;
						reasonText='';
					}
					if(ev==='error'&&d.message){
						if(!bb){bb=rMsg({role:'asst',content:'',parts:[]})}
						bb.innerHTML='<div style="color:var(--err);padding:6px">'+esc(d.message)+'</div>';
						st('Error',false);
						_notifyOnComplete('AI Error',d.message);
					}
				}catch(e){}
			});
		};
		xhr.onload=function(){
			str=false;
			document.getElementById('ocSend').style.display='flex';
			document.getElementById('ocStopBtn').style.display='none';
			cS=null;
			if(thinkBubble){
				var msgEl=thinkBubble.closest('.ocMsg');
				if(msgEl&&msgEl.parentNode)msgEl.parentNode.removeChild(msgEl);
				thinkBubble=null;
			}
			if(xhr.status!==200&&xhr.status!==0){
				var em='Error ('+xhr.status+')';
				try{var ej=JSON.parse(xhr.responseText);if(ej.data&&(ej.data.message||ej.data.error))em=ej.data.message||ej.data.error}catch(e){}
				if(!bb){bb=rMsg({role:'asst',content:'',parts:[]})}
				bb.innerHTML='<div style="color:var(--err);padding:6px">'+esc(em)+'</div>';
				st('Error',false);
			}
			if(bb){var c=bb.querySelector('.ocCu');if(c)c.remove()}
			st('Ready',true);
			document.getElementById('ocChat').scrollTop=1e9;
			loadSL();
		};
		xhr.onerror=function(){
			str=false;
			document.getElementById('ocSend').style.display='flex';
			document.getElementById('ocStopBtn').style.display='none';
			cS=null;
			if(bb)bb.innerHTML='<span style="color:var(--err)">Connection error</span>';
			st('Error',false);
			_notifyOnComplete('AI Error','Connection error');
		};
		xhr.send(params);
	};

	var mv=document.getElementById('ocModel').value;
	if(mv){
		var pp=mv.split('/');
		api('start',{provider:pp[0],model:pp.slice(1).join('/'),mode:document.getElementById('ocMode').value},function(){doSend()});
	}else{
		doSend();
	}
}

function loadP(){
	api('providers',null,function(e,d){
		if(e||!d)return;
		pv=d;
		syncModelCtx(d);
		var ms=document.getElementById('ocModel');
		ms.innerHTML='<option value="">Select model</option>';
		var g={};
		d.forEach(function(p){
			if(!p.connected)return;
			var m=p.models||{};
			for(var k in m){
				var v=typeof m[k]==='object'?m[k].name:m[k];
				if(!g[p.name])g[p.name]=[];
				g[p.name].push({id:k,name:v,provider:p.id});
			}
		});
		for(var gn in g){
			var og=document.createElement('optgroup');
			og.label=gn;
			g[gn].forEach(function(m){
				var o=document.createElement('option');
				o.value=m.provider+'/'+m.id;
				o.textContent=m.name;
				og.appendChild(o);
			});
			ms.appendChild(og);
		}

		if(selModel){
			ms.value=selModel;
			if(ms.value!==selModel){
				var opts=ms.querySelectorAll('option');
				for(var i=0;i<opts.length;i++){
					if(opts[i].value===selModel){ms.selectedIndex=i;break}
				}
			}
		}else{
		var freeModel=null;
		var opts=ms.querySelectorAll('option');
		for(var i=0;i<opts.length;i++){
			if(opts[i].value==='opencode_zen/minimax-m2.5-free'){
				freeModel=opts[i].value;
				break;
			}
		}
		if(!freeModel){
			for(var i=0;i<opts.length;i++){
				if(opts[i].value.indexOf('opencode_zen/')===0){
					freeModel=opts[i].value;
					break;
				}
			}
		}
		if(freeModel){
				ms.value=freeModel;
				var pp=freeModel.split('/');
				api('start',{provider:pp[0],model:pp.slice(1).join('/'),mode:document.getElementById('ocMode').value},function(){});
			}
		}
		rPL(d);
	});
}

function rPL(d,query){
	var l=document.getElementById('ocPL');
	l.innerHTML='';
	var sorted=d.slice().sort(function(a,b){
		if(a.connected&&!b.connected)return -1;
		if(!a.connected&&b.connected)return 1;
		return(a.name||'').localeCompare(b.name||'');
	});
	var q=(query||'').toLowerCase();
	sorted.forEach(function(p){
		if(p.is_custom_type)return;
		var div=document.createElement('div');
		div.className='ocPi';
		div.dataset.id=p.id;
		div.dataset.name=(p.name||'').toLowerCase();
		if(q&&div.dataset.name.indexOf(q)===-1){
			div.className='ocPi ocPiHide';
		}
		var modelCount=Object.keys(p.models||{}).length;
		var sub=modelCount?modelCount+' model'+(modelCount>1?'s':''):'No models';
		var badge=p.connected?'<span class="ocPiB">Connected</span>':'<span class="ocPiB ocPiBo">Not set</span>';
		var btn=p.connected
			?'<button class="ocB ocBs" data-a="dis" data-id="'+p.id+'">Disconnect</button>'
			:'<button class="ocB ocBs" data-a="con" data-id="'+p.id+'">Connect</button>';
		div.innerHTML='<span class="ocPiN">'+p.name+'<span class="ocPiSub">'+sub+'</span></span>'+badge+btn;
		l.appendChild(div);
	});
}

function isCustomType(id){
	return id==='custom'||(id&&id.indexOf('custom:')===0);
}

function toggleCustFields(id){
	var show=isCustomType(id);
	document.getElementById('ocBUG').style.display=show?'block':'none';
	document.getElementById('ocCustNameG').style.display=(id==='custom')?'block':'none';
	document.getElementById('ocCustModelsG').style.display=show?'block':'none';
}

var cP=null;
function openConPopup(providerId){
	cP=providerId||null;
	var sel=document.getElementById('ocProvSel');
	sel.innerHTML='';
	var found=false;
	pv.forEach(function(p){
		if(p.no_key)return;
		if(p.is_custom_type)return;
		var o=document.createElement('option');
		o.value=p.id;
		o.textContent=p.name+(p.connected?' (Connected)':'');
		sel.appendChild(o);
		if(p.id===providerId){sel.value=providerId;found=true}
	});
	var customOpt=document.createElement('option');
	customOpt.value='custom';
	customOpt.textContent='Custom Provider (OpenAI Compatible)';
	sel.appendChild(customOpt);
	if(providerId&&isCustomType(providerId)){
		sel.value='custom';
		if(isCustomType(providerId)){
			var p=pv.find(function(x){return x.id===providerId});
			if(p){
				document.getElementById('ocCustName').value=p.name||'';
				var mArr=[];
				for(var k in (p.models||{})){mArr.push(k+'='+(typeof p.models[k]==='object'?p.models[k].name:p.models[k]));}
				document.getElementById('ocCustModels').value=mArr.join(', ');
			}
			document.getElementById('ocCustNameG').style.display='none';
		}
	}else{
		document.getElementById('ocCustName').value='';
		document.getElementById('ocCustModels').value='';
	}
	if(!found&&providerId)sel.value=providerId;
	document.getElementById('ocAK').value='';
	document.getElementById('ocBU').value='';
	toggleCustFields(providerId);
	if(providerId&&isCustomType(providerId)){
		var p=pv.find(function(x){return x.id===providerId});
		if(p&&p.base_url){
			document.getElementById('ocBU').value=p.base_url;
		}
	}
	if(providerId){
		var p=pv.find(function(x){return x.id===providerId});
		if(p&&p.connected){
			document.getElementById('ocConTitle').textContent='Edit '+p.name;
		}else{
			document.getElementById('ocConTitle').textContent='Connect '+p.name;
		}
	}else{
		document.getElementById('ocConTitle').textContent='Add Connection';
	}
	document.getElementById('ocConMod').classList.add('open');
}
document.getElementById('ocPL').addEventListener('click',function(e){
	var b=e.target.closest('button');
	if(!b)return;
	var a=b.dataset.a,id=b.dataset.id;
	if(a==='con'){
		openConPopup(id);
	}else if(a==='dis'){
		if(confirm('Disconnect?'))api('settings_delete',{provider_id:id},function(){loadP()});
	}
});
document.getElementById('ocAddCon').addEventListener('click',function(){
	loadP();
	setTimeout(function(){openConPopup(null)},200);
});
document.getElementById('ocProvSel').addEventListener('change',function(){
	var id=this.value;
	cP=id;
	toggleCustFields(id);
	if(id==='custom'){
		document.getElementById('ocCustNameG').style.display='block';
		document.getElementById('ocConTitle').textContent='Add Custom Provider';
	}else{
		var p=pv.find(function(x){return x.id===id});
		if(p){
			document.getElementById('ocConTitle').textContent=p.connected?'Edit '+p.name:'Connect '+p.name;
		}
	}
});
document.getElementById('ocSav').addEventListener('click',function(){
	var selVal=document.getElementById('ocProvSel').value;
	var pid=selVal;
	var d={};
	if(selVal==='custom'){
		var custName=document.getElementById('ocCustName').value.trim();
		if(!custName){alert('Please enter a provider name');return;}
		pid='custom:'+custName.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'');
		d.name=custName;
	}else if(cP&&isCustomType(cP)){
		pid=cP;
	}
	var k=document.getElementById('ocAK').value.trim();
	var bu=document.getElementById('ocBU').value.trim();
	var md=document.getElementById('ocCustModels').value.trim();
	if(isCustomType(pid)&&!bu){alert('Please enter a Base URL for the custom provider');return;}
	d.provider_id=pid;
	d.api_key=k;
	if(bu)d.base_url=bu;
	if(md&&isCustomType(pid)){
		var models={};
		md.split(',').forEach(function(m){
			var parts=m.trim().split('=');
			if(parts.length===2){
				models[parts[0].trim()]=parts[1].trim();
			}else if(parts.length===1&&parts[0].trim()){
				models[parts[0].trim()]=parts[0].trim();
			}
		});
		d.models=JSON.stringify(models);
	}
	api('settings_save',d,function(e,r){
		if(!e&&r&&r.success){
			document.getElementById('ocConMod').classList.remove('open');
			cP=null;
			loadP();
		}else alert(e||(r&&r.error)||'Failed');
	});
});
document.getElementById('ocTst').addEventListener('click',function(){
	var selVal=document.getElementById('ocProvSel').value;
	var pid=cP||selVal;
	if(!pid)return;
	var td={provider_id:pid,api_key:document.getElementById('ocAK').value.trim()};
	if(isCustomType(pid)){
		var bu=document.getElementById('ocBU').value.trim();
		if(bu)td.base_url=bu;
		var testPid=pid;
		if(selVal==='custom'){
			var custName=document.getElementById('ocCustName').value.trim();
			if(custName)testPid='custom:'+custName.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'');
			else testPid='custom:test';
		}
		td.provider_id=testPid;
		var md=document.getElementById('ocCustModels').value.trim();
		if(md){
			var models={};
			md.split(',').forEach(function(m){
				var parts=m.trim().split('=');
				if(parts.length===2){
					models[parts[0].trim()]=parts[1].trim();
				}else if(parts.length===1&&parts[0].trim()){
					models[parts[0].trim()]=parts[0].trim();
				}
			});
			td.models=JSON.stringify(models);
			var firstModel=Object.keys(models)[0];
			if(firstModel)td.model=firstModel;
		}
	}
	st('Testing...',true);
	api('test_connection',td,function(e,r){
		alert(!e&&r&&r.success?'OK':'Failed: '+(e||(r&&r.error)||''));
		st('Ready',true);
	});
});
function closeConPopup(){
	document.getElementById('ocConMod').classList.remove('open');
	cP=null;
}
document.getElementById('ocCan2').addEventListener('click',closeConPopup);
document.getElementById('ocConC').addEventListener('click',closeConPopup);
document.getElementById('ocConMod').addEventListener('click',function(e){
	if(e.target===this)closeConPopup();
});

document.getElementById('ocSetBtn').addEventListener('click',function(){
	document.getElementById('ocSetMod').classList.add('open');
	loadP();
});
document.getElementById('ocSetC').addEventListener('click',function(){
	document.getElementById('ocSetMod').classList.remove('open');
});
document.getElementById('ocSetMod').addEventListener('click',function(e){
	if(e.target===this)this.classList.remove('open');
});
document.getElementById('ocSetSearch').addEventListener('input',function(){
	if(!pv||!pv.length){
		loadP();
		return;
	}
	rPL(pv,this.value);
});
document.getElementById('ocCtxMod').addEventListener('click',function(e){
	if(e.target===this)this.classList.remove('open');
});
document.getElementById('ocCtxC').addEventListener('click',function(){
	document.getElementById('ocCtxMod').classList.remove('open');
});

function showCtxDetail(){
	var bd=document.getElementById('ocCtxBd');
	var u=lastStats.usage||{};
	var inT=u.input_tokens||0;
	var outT=u.output_tokens||0;
	var cachedT=u.cached_tokens||0;
	var ctxT=lastStats.context_tokens||0;
	var tools=lastStats.tool_calls||0;
	var iters=lastStats.iterations||0;
	var cost=lastStats.cost||0;
	if(!cachedT&&rMsgs._lastMsgs){
		rMsgs._lastMsgs.forEach(function(m){
			if(m.role==='assistant'&&m.usage){
				cachedT+=(m.usage.cached_tokens||0)+(m.usage.cache_read_input_tokens||0)+(m.usage.cache_creation_input_tokens||0);
				if(m.usage.prompt_tokens_details&&m.usage.prompt_tokens_details.cached_tokens)cachedT+=m.usage.prompt_tokens_details.cached_tokens;
				if(!inT)inT+=(m.usage.prompt_tokens||0)+(m.usage.input_tokens||0);
			}
		});
	}
	var mv=document.getElementById('ocModel').value;
	var isFreeModel=mv&&mv.indexOf('opencode_zen/')===0;
	var msgs=0;
	var ch=document.getElementById('ocCI');
	ch.querySelectorAll('.ocMsg').forEach(function(){msgs++});
	var html='';
	html+='<div class="ocCtxR"><span class="ocCtxL">Model</span><span class="ocCtxV">'+(document.getElementById('ocModel').selectedOptions[0]?document.getElementById('ocModel').selectedOptions[0].textContent:'-')+'</span></div>';
	html+='<div class="ocCtxR"><span class="ocCtxL">Provider</span><span class="ocCtxV">'+(selModel?selModel.split('/')[0]:'-')+'</span></div>';
	html+='<div class="ocCtxR"><span class="ocCtxL">Mode</span><span class="ocCtxV">'+document.getElementById('ocMode').value+'</span></div>';
	html+='<div class="ocCtxR"><span class="ocCtxL">Messages in view</span><span class="ocCtxV">'+msgs+'</span></div>';
	html+='<div class="ocCtxS"><div class="ocCtxSt">Tokens</div>';
	html+='<div class="ocCtxBr"><span>Context size</span><span>'+fmtTok(ctxT)+'</span></div>';
	html+='<div class="ocCtxBr"><span>Input tokens</span><span>'+fmtTok(inT)+'</span></div>';
	html+='<div class="ocCtxBr"><span>Cached tokens</span><span>'+(cachedT>0?fmtTok(cachedT)+(inT>0?' ('+Math.round(cachedT/inT*100)+'%)':''):'0')+'</span></div>';
	html+='<div class="ocCtxBr"><span>Output tokens</span><span>'+fmtTok(outT)+'</span></div>';
	html+='<div class="ocCtxBr"><span>Total tokens</span><span>'+fmtTok(inT+outT)+'</span></div>';
	html+='</div>';
	html+='<div class="ocCtxS"><div class="ocCtxSt">Session</div>';
	html+='<div class="ocCtxBr"><span>Iterations</span><span>'+iters+'</span></div>';
	html+='<div class="ocCtxBr"><span>Tool calls</span><span>'+tools+'</span></div>';
	if(!isFreeModel&&cost>0){
		html+='<div class="ocCtxBr"><span>Cost</span><span>$'+(cost<0.01&&cost>0?cost.toFixed(6):cost.toFixed(4))+'</span></div>';
	}
	html+='</div>';
	bd.innerHTML=html;
	document.getElementById('ocCtxMod').classList.add('open');
}

document.getElementById('ocStat').addEventListener('click',function(e){
	e.stopPropagation();
	showCtxDetail();
});
document.getElementById('ocAttBtn').addEventListener('click',function(){
	document.getElementById('ocFile').click();
});
document.getElementById('ocFile').addEventListener('change',function(){
	var files=this.files;
	if(!files.length)return;
	var loaded=0;
	var total=files.length;
	Array.from(files).forEach(function(f){
		if(f.size>1048576){
			loaded++;
			pendingFiles.push({name:f.name,content:'[File too large: '+f.size+' bytes]',size:f.size});
			if(loaded===total){
				var names=pendingFiles.map(function(x){return x.name}).join(', ');
				var inp=document.getElementById('ocIn');
				inp.value+=(inp.value?'\n':'')+'[Attached: '+names+']';
				inp.dispatchEvent(new Event('input'));
			}
			return;
		}
		var reader=new FileReader();
		reader.onload=function(e){
			pendingFiles.push({name:f.name,content:e.target.result,size:f.size});
			loaded++;
			if(loaded===total){
				var names=pendingFiles.map(function(x){return x.name}).join(', ');
				var inp=document.getElementById('ocIn');
				inp.value+=(inp.value?'\n':'')+'[Attached: '+names+']';
				inp.dispatchEvent(new Event('input'));
			}
		};
		reader.readAsText(f);
	});
});
document.getElementById('ocSend').addEventListener('click',function(){send()});
document.getElementById('ocStopBtn').addEventListener('click',function(){
	if(cS){cS.abort();cS=null}
	str=false;
	document.getElementById('ocSend').style.display='flex';
	this.style.display='none';
	st('Stopped',false);
});

var slashCmds=[
	{cmd:'/plan',desc:'Switch to Plan mode (read-only)'},
	{cmd:'/build',desc:'Switch to Build mode (read+write)'},
	{cmd:'/clear',desc:'Clear current conversation'},
	{cmd:'/export',desc:'Download conversation as Markdown'},
	{cmd:'/help',desc:'Show keyboard shortcuts and commands'}
];

document.getElementById('ocIn').addEventListener('input',function(){
	this.style.height='40px';
	this.style.height=Math.min(this.scrollHeight,150)+'px';
	var v=this.value;
	var hint=document.getElementById('ocSlash');
	if(v.charAt(0)==='/'&&v.indexOf(' ')===-1){
		var q=v.toLowerCase();
		var matches=slashCmds.filter(function(c){return c.cmd.indexOf(q)===0});
		if(matches.length){
			hint.innerHTML=matches.map(function(c){return '<div class="ocSHItem" data-cmd="'+c.cmd+'"><span class="ocSHCmd">'+c.cmd+'</span><span class="ocSHDesc">'+c.desc+'</span></div>'}).join('');
			hint.classList.add('open');
			hint.querySelectorAll('.ocSHItem').forEach(function(el){
				el.addEventListener('click',function(){
					document.getElementById('ocIn').value=this.dataset.cmd+' ';
					hint.classList.remove('open');
					document.getElementById('ocIn').focus();
				});
			});
		}else{hint.classList.remove('open')}
	}else{hint.classList.remove('open')}
});
document.getElementById('ocIn').addEventListener('keydown',function(e){
	if(e.key==='Enter'&&!e.shiftKey){
		var hint=document.getElementById('ocSlash');
		if(hint.classList.contains('open')){
			var first=hint.querySelector('.ocSHItem');
			if(first){e.preventDefault();first.click();return}
		}
		e.preventDefault();send();
	}
});

function renderDiff(diffText){
	if(!diffText)return '';
	var lines=diffText.split('\n');
	var html='<div class="ocDiff"><div class="ocDiffH"><span>Diff</span></div>';
	var lineNum=0;
	lines.forEach(function(l){
		lineNum++;
		var cls='ctx';
		if(l.charAt(0)==='+')cls='add';
		else if(l.charAt(0)==='-')cls='del';
		html+='<div class="ocDiffL '+cls+'">'+esc(l)+'</div>';
	});
	html+='</div>';
	return html;
}

function exportChat(){
	var msgs=document.querySelectorAll('#ocCI .ocMsg');
	var md='';
	msgs.forEach(function(el){
		var isUser=el.classList.contains('u');
		md+=(isUser?'## User\n\n':'## Assistant\n\n');
		var textEl=el.querySelector('.ocMd');
		if(textEl){
			md+=textEl.innerText||'';
		}else{
			var thinkEl=el.querySelector('.ocThinkT');
			if(thinkEl)md+='*'+thinkEl.textContent+'*';
		}
		var tools=el.querySelectorAll('.ocTk');
		tools.forEach(function(t){
			var name=t.querySelector('.ocTkN');
			var body=t.querySelector('.ocTkB');
			md+='\n\n**Tool: '+(name?name.textContent:'unknown')+'**\n```\n'+(body?body.innerText:'')+'\n```\n';
		});
		md+='\n\n---\n\n';
	});
	var blob=new Blob([md],{type:'text/markdown'});
	var url=URL.createObjectURL(blob);
	var a=document.createElement('a');
	a.href=url;a.download='chat-'+new Date().toISOString().slice(0,10)+'.md';
	document.body.appendChild(a);a.click();document.body.removeChild(a);
	URL.revokeObjectURL(url);
}

modelCtx={
	'gpt-4o':128000,'gpt-4o-mini':128000,'gpt-4-turbo':128000,
	'claude-sonnet-4-20250514':200000,'claude-opus-4-20250514':200000,'claude-3-5-sonnet-20241022':200000,'claude-3-5-haiku-20241022':200000,
	'gemini-2.5-pro':1048576,'gemini-2.5-flash':1048576,'gemini-1.5-pro':2097152,
	'deepseek-chat':64000,'deepseek-coder':64000,
	'llama-3.3-70b-versatile':128000,'llama-3.1-8b-instant':128000,
	'MiniMax-Text-01':1000000,'MiniMax-M1':1000000,
	'big-pickle':32000,'minimax-m2.5-free':32000,'nemotron-3-super-free':32000,'trinity-large-preview-free':32000,
	'claude-sonnet-4':200000,'claude-opus-4-7':200000,'gpt-5.4':128000,'gpt-5.4-nano':128000,
	'gemini-3-flash':1048576,'gemini-3.1-pro':1048576,'glm-5':128000,'minimax-m2.7':1000000,'kimi-k2.6':128000,'qwen3.6-plus':128000
};
function syncModelCtx(providers){
	providers.forEach(function(p){
		if(!p.models)return;
		for(var mid in p.models){
			if(!modelCtx[mid]){
				var m=p.models[mid];
				modelCtx[mid]=(typeof m==='object'&&m.context_window)?m.context_window:128000;
			}
		}
	});
}

document.addEventListener('keydown',function(e){
	if(e.key==='Escape'){
		if(str&&cS){cS.abort();cS=null;str=false;document.getElementById('ocSend').style.display='flex';document.getElementById('ocStopBtn').style.display='none';st('Stopped',false)}
		var openModals=document.querySelectorAll('.ocMo.open,.ocFv.open');
		openModals.forEach(function(m){m.classList.remove('open')});
		var slashH=document.getElementById('ocSlash');if(slashH)slashH.classList.remove('open');
		return;
	}
	var inp=document.getElementById('ocIn');
	if(document.activeElement===inp)return;
	if((e.ctrlKey||e.metaKey)&&e.shiftKey&&e.key==='N'){e.preventDefault();newS()}
	if((e.ctrlKey||e.metaKey)&&e.shiftKey&&e.key==='P'){e.preventDefault();document.getElementById('ocMode').value='plan';api('set_mode',{mode:'plan'},function(){})}
	if((e.ctrlKey||e.metaKey)&&e.key===','){e.preventDefault();document.getElementById('ocSetMod').classList.add('open');loadP()}
});

var ocProject=document.getElementById('ocProject');
function loadSidebarProjects(){
	api('projects_list',null,function(e,d){
		if(e||!d)return;
		if(!ocProject)return;
		ocProject.innerHTML='<option value="__new__">+ Add New Project</option>';
		d.forEach(function(p){
			var o=document.createElement('option');
			o.value=p.project_id;
			o.textContent=p.name||p.path;
			if(p.project_id===PID) o.selected=true;
			ocProject.appendChild(o);
		});
	});
}
if(ocProject){
	loadSidebarProjects();
	ocProject.addEventListener('change',function(){
		var val=this.value;
		if(val==='__new__'){
			this.value=PID;
			openProjectModal();
			setTimeout(function(){
				document.getElementById('ocProjNewForm').style.display='block';
				document.getElementById('ocProjName').value='';
				document.getElementById('ocProjPath').value='';
				if(wordpressInstalls&&wordpressInstalls.length){
					document.getElementById('ocProjWpSection').style.display='block';
				}
			},300);
			return;
		}
		if(val && val!==PID){
			switchToProject(val);
		}
	});
}

document.getElementById('ocModel').addEventListener('change',function(){
	var v=this.value;
	if(!v)return;
	var p=v.split('/');
	api('start',{provider:p[0],model:p.slice(1).join('/'),mode:document.getElementById('ocMode').value},function(){});
});
document.getElementById('ocMode').addEventListener('change',function(){
	api('set_mode',{mode:this.value},function(){});
});
document.getElementById('ocNew').addEventListener('click',newS);
document.getElementById('ocTogSb').addEventListener('click',function(){
	var sb=document.getElementById('ocSb');
	if(window.innerWidth<=768){
		if(sb.classList.contains('resp')){
			sb.classList.remove('resp');
			sb.classList.add('hid');
		}else{
			sb.classList.remove('hid');
			sb.classList.add('resp');
		}
	}else{
		sb.classList.toggle('hid');
	}
});
window.addEventListener('resize',function(){
	var sb=document.getElementById('ocSb');
	if(window.innerWidth<=768){
		sb.classList.add('hid');
		sb.classList.remove('resp');
	}else{
		sb.classList.remove('resp');
		if(!sb.classList.contains('hid'))sb.classList.remove('hid');
	}
});
// Initial state
if(window.innerWidth<=768){
	document.getElementById('ocSb').classList.add('hid');
}

var cs=localStorage.getItem('oc-cs')||'light';
function applyCS(c){
	document.documentElement.setAttribute('data-cs',c);
	document.getElementById('hljs-theme').href=c==='dark'
		?'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css'
		:'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css';
	localStorage.setItem('oc-cs',c);
}
applyCS(cs);
document.getElementById('ocThBtn').addEventListener('click',function(){
	cs=cs==='light'?'dark':'light';
	applyCS(cs);
});

api('status',null,function(e,d){
	if(!e&&d){
		if(d.session){
			if(d.session.provider&&d.session.model){
				selModel=d.session.provider+'/'+d.session.model;
				selMode=d.session.mode||'build';
			}
			if(d.session.mode)document.getElementById('ocMode').value=d.session.mode;
			var urlSession=new URL(window.location).searchParams.get('session');
			if(urlSession){
				aC=urlSession;
				loadC(aC);
			}else if(d.session.active_conversation){
				aC=d.session.active_conversation;
				loadC(aC);
			}
		}
		var hc=(d.providers||[]).some(function(p){return p.connected});
		st(hc?'Ready':'No provider connected',hc);
	}
});

api('project_info',null,function(e,d){
	if(!e&&d){
		var overviewText=(d.overview||'');
		var lines=overviewText.split('\n');
		var typeLine=lines[0]||'';
		var pathLine=lines[1]||'';
		var typeMatch=typeLine.match(/Project type:\s*(.+)/i);
		var pathMatch=pathLine.match(/Project path:\s*(.+)/i);
		var dispType=typeMatch?typeMatch[1].trim():(d.type||'unknown');
		var dispPath=pathMatch?pathMatch.trim():SP;
		if(dispPath&&HD&&dispPath.indexOf(HD)===0) dispPath=dispPath.substring(HD.length+1);
		ctx='<b>Project Type:</b> '+esc(dispType)+'<br>'
			+'<b>Path:</b> <code>'+esc(dispPath||SP)+'</code><br>';
		var restOfOverview=lines.slice(2).join('\n').trim();
		if(restOfOverview){
			ctx+='<br>'+esc(restOfOverview.substring(0,300))+(restOfOverview.length>300?'...':'');
		}
		rCtx();
	}
});

loadP();
loadSL();

var ftOpen=false,ftLoaded=false,ftPath='';
document.getElementById('ocFtBtn').addEventListener('click',function(){
	var panel=document.getElementById('ocFt');
	if(ftOpen==false){
		panel.classList.add('open');
		ftOpen=true;
		if(!ftLoaded){loadFT('/');ftLoaded=true}
	}else{
		panel.classList.remove('open');
		ftOpen=false;
	}
});
document.getElementById('ocFtRef').addEventListener('click',function(){loadFT(ftPath||'/')});
document.getElementById('ocFv').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')});
document.getElementById('ocFvC').addEventListener('click',function(){document.getElementById('ocFv').classList.remove('open')});

function loadFT(path){
	ftPath=path;
	var u=A+'&ai_php_api=file_tree&csrf_token='+C+'&path='+encodeURIComponent(path)+'&depth=1';
	var x=new XMLHttpRequest();
	x.open('GET',u,true);
	x.setRequestHeader('Accept','application/json');
	x.onload=function(){
		if(x.status===200){
			try{renderFT(JSON.parse(x.responseText),path)}
			catch(e){}
		}
	};
	x.send();
}

function renderFT(data,basePath){
	var el=document.getElementById('ocFtL');
	el.innerHTML='';
	if(data.directories){
		data.directories.sort(function(a,b){return a.name.localeCompare(b.name)});
		data.directories.forEach(function(d){
			if(d.name==='.git')return;
			var item=document.createElement('div');
			item.className='ocFtI';
			item.dataset.path=d.path;
			item.dataset.type='dir';
			item.dataset.loaded='0';
			item.innerHTML='<span class="ocFtIc"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span><span class="ocFtIn">'+esc(d.name)+'</span>';
			item.addEventListener('click',function(ev){
				ev.stopPropagation();
				var loaded=this.dataset.loaded==='1';
				var open=this.dataset.open==='1';
				if(!loaded){
					this.dataset.loaded='1';
					var sub=document.createElement('div');
					sub.className='ocFtSub';
					sub.style.display='block';
					this.after(sub);
					loadFTSub(d.path,sub,this);
				}else{
					var sub=this.nextElementSibling;
					if(sub&&sub.className==='ocFtSub'){
						sub.style.display=open?'none':'block';
					}
				}
				this.dataset.open=open?'0':'1';
				this.querySelector('.ocFtIc').innerHTML=open?'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>':'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
			});
			el.appendChild(item);
		});
	}
	if(data.files){
		data.files.sort(function(a,b){return a.name.localeCompare(b.name)});
		data.files.forEach(function(f){
			var item=document.createElement('div');
			item.className='ocFtI';
			item.dataset.path=f.path;
			item.dataset.type='file';
			var sz=f.size?fmtSize(f.size):'';
			item.innerHTML='<span class="ocFtIc" style="opacity:.4"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg></span><span class="ocFtIn">'+esc(f.name)+'</span>'
				+(sz?'<span class="ocFtS">'+sz+'</span>':'');
			item.addEventListener('click',function(ev){
				ev.stopPropagation();
				viewFile(f.path,f.name);
			});
			el.appendChild(item);
		});
	}
}

function loadFTSub(path,container,toggle){
	var u=A+'&ai_php_api=file_tree&csrf_token='+C+'&path='+encodeURIComponent(path)+'&depth=1';
	var x=new XMLHttpRequest();
	x.open('GET',u,true);
	x.setRequestHeader('Accept','application/json');
	x.onload=function(){
		if(x.status===200){
			try{
				var data=JSON.parse(x.responseText);
				container.innerHTML='';
				if(data.directories){
					data.directories.sort(function(a,b){return a.name.localeCompare(b.name)});
					data.directories.forEach(function(d){
						var item=document.createElement('div');
						item.className='ocFtI';
						item.dataset.path=d.path;
						item.dataset.type='dir';
						item.dataset.loaded='0';
						item.style.paddingLeft='16px';
			item.innerHTML='<span class="ocFtIc"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span><span class="ocFtIn">'+esc(d.name)+'</span>';
						item.addEventListener('click',function(ev){
							ev.stopPropagation();
							var loaded=this.dataset.loaded==='1';
							var open=this.dataset.open==='1';
							if(!loaded){
								this.dataset.loaded='1';
								var sub=document.createElement('div');
								sub.className='ocFtSub';
								sub.style.display='block';
								this.after(sub);
								loadFTSub(d.path,sub,this);
							}else{
								var sub=this.nextElementSibling;
								if(sub&&sub.className==='ocFtSub'){
									sub.style.display=open?'none':'block';
								}
							}
							this.dataset.open=open?'0':'1';
this.querySelector('.ocFtIc').innerHTML=open?'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>':'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
						});
						container.appendChild(item);
					});
				}
				if(data.files){
					data.files.sort(function(a,b){return a.name.localeCompare(b.name)});
					data.files.forEach(function(f){
						var item=document.createElement('div');
						item.className='ocFtI';
						item.dataset.path=f.path;
						item.dataset.type='file';
						item.style.paddingLeft='16px';
						var sz=f.size?fmtSize(f.size):'';
			item.innerHTML='<span class="ocFtIc" style="opacity:.4"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg></span><span class="ocFtIn">'+esc(f.name)+'</span>'
							+(sz?'<span class="ocFtS">'+sz+'</span>':'');
						item.addEventListener('click',function(ev){
							ev.stopPropagation();
							viewFile(f.path,f.name);
						});
						container.appendChild(item);
					});
				}
				if(!data.directories&&!data.files){
					container.innerHTML='<div style="padding:4px 16px;color:var(--t3);font-size:11px">Empty</div>';
				}
			}catch(e){}
		}
	};
	x.send();
}

function viewFile(path,name){
	var u=A+'&ai_php_api=read_file&csrf_token='+C+'&path='+encodeURIComponent(path);
	var x=new XMLHttpRequest();
	x.open('GET',u,true);
	x.setRequestHeader('Accept','application/json');
	x.onload=function(){
		if(x.status===200){
			try{
				var d=JSON.parse(x.responseText);
				document.getElementById('ocFvT').textContent=name||path;
				var pre=document.getElementById('ocFvPre');
				if(d.content!==undefined){
					try{pre.innerHTML=hljs.highlightAuto(d.content).value}
					catch(e){pre.textContent=d.content}
				}else{
					pre.textContent=d.error||'Failed to read file';
				}
				document.getElementById('ocFv').classList.add('open');
			}catch(e){}
		}
	};
	x.send();
}

function fmtSize(b){
	if(b<1024)return b+'B';
	if(b<1048576)return (b/1024).toFixed(1)+'K';
	return (b/1048576).toFixed(1)+'M';
}
function fmtTok(n){
	if(n>=1000000)return (n/1000000).toFixed(1)+'M';
	if(n>=1000)return (n/1000).toFixed(1)+'k';
	return n;
}
function updateURL(convId){
	var u=new URL(window.location);
	if(convId)u.searchParams.set('session',convId);
	else u.searchParams.delete('session');
history.replaceState(null,'',u.toString());
}

// Toast notifications
function toast(msg,type,dur){
	var t=document.getElementById('ocToast');
	var d=document.createElement('div');
	d.className='ocToastI '+(type||'info');
	d.textContent=msg;
	t.appendChild(d);
	setTimeout(function(){d.classList.add('hide');setTimeout(function(){d.remove()},300)},dur||3000);
}

// Sound notification using Web Audio API (played on tab focus, not in background)
var _audioCtx=null;
function _playChime(){
	try{
		if(!_audioCtx)_audioCtx=new(window.AudioContext||window.webkitAudioContext)();
		if(_audioCtx.state==='suspended')_audioCtx.resume();
		var t=_audioCtx.currentTime;
		function _note(freq,start,dur,vol){
			var o=_audioCtx.createOscillator(),g=_audioCtx.createGain();
			o.type='sine';o.frequency.value=freq;
			o.connect(g);g.connect(_audioCtx.destination);
			g.gain.setValueAtTime(0,t+start);
			g.gain.linearRampToValueAtTime(vol,t+start+0.015);
			g.gain.setValueAtTime(vol,t+start+dur*0.15);
			g.gain.exponentialRampToValueAtTime(0.001,t+start+dur);
			o.start(t+start);o.stop(t+start+dur+0.01);
		}
		_note(880,0,0.12,0.2);
		_note(660,0.1,0.15,0.16);
	}catch(e){}
}

// Browser notification + sound + title flash when chat completes in background tab
function _notifyOnComplete(title,body){
	if(!document.hidden)return;
	if(_notifyPending)return;
	_notifyPending=true;
	document.title='\u2728 '+(body||'Response ready');
	_playChime();
	if(typeof Notification!=='undefined'&&Notification.permission==='granted'){
		var n=new Notification(title,{body:body,tag:'ai-chat'});
		n.onclick=function(){window.focus();n.close()};
	}
}

// Reset title when user returns to tab after background notification
document.addEventListener('visibilitychange',function(){
	if(!document.hidden&&_notifyPending){
		_notifyPending=false;
		document.title=_origTitle;
	}
});

// @-Mention file resolution in send()
var origSend=send;
send=function(){
	var inp=document.getElementById('ocIn');
	var text=inp.value.trim();
	if(!text||str)return;
	var mentions=text.match(/@([\w\/.\-]+)/g);
	if(mentions){
		var resolved=0;
		var files={};
		mentions.forEach(function(m){
			var fp=m.substring(1);
			var u=A+'&ai_php_api=resolve_file&csrf_token='+C+'&path='+encodeURIComponent(fp);
			var x=new XMLHttpRequest();
			x.open('GET',u,false);
			x.setRequestHeader('Accept','application/json');
			x.send();
			if(x.status===200){
				try{
					var d=JSON.parse(x.responseText);
					if(d.content){
						files[fp]=d.content;
						resolved++;
					}
				}catch(e){}
			}
		});
		if(resolved>0){
			var fileCtx='';
			for(var f in files){
				fileCtx+='\n\n--- @'+f+' ---\n'+files[f]+'\n';
			}
			text+='\n[Attached files:]'+fileCtx;
		}
		inp.value=text;
	}
	origSend();
};

// Message actions (edit, regenerate)
document.getElementById('ocCI').addEventListener('mouseover',function(e){
	var msg=e.target.closest('.ocMsg');
	if(!msg||msg.querySelector('.ocMsgAct'))return;
	var isU=msg.classList.contains('u');
	var msgs=document.querySelectorAll('#ocCI .ocMsg');
	var isLast=msg===msgs[msgs.length-1];
	var act=document.createElement('div');
	act.className='ocMsgAct';
	if(isU){
		var editBtn=document.createElement('button');
		editBtn.className='ocMsgActB';
		editBtn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>';
		editBtn.title='Edit & Resend';
		editBtn.addEventListener('click',function(ev){
			ev.stopPropagation();
			var bb=msg.querySelector('.ocMbb');
			if(!bb)return;
			var oldText=bb.textContent||'';
			bb.contentEditable='true';
			bb.focus();
			bb.style.background='var(--input)';
			bb.style.color='var(--t1)';
			bb.style.border='1px solid var(--ac)';
			bb.style.borderRadius='var(--r)';
			bb.style.padding='8px 12px';
			var saveBtn=document.createElement('button');
			saveBtn.textContent='Send';
			saveBtn.className='ocB ocBP';
			saveBtn.style.cssText='position:absolute;bottom:-30px;right:0;font-size:11px;height:24px;z-index:100';
			msg.style.position='relative';
			msg.appendChild(saveBtn);
			saveBtn.addEventListener('click',function(){
				var newText=bb.textContent.trim();
				bb.contentEditable='false';
				bb.style.cssText='';
				saveBtn.remove();
				if(newText&&newText!==oldText){
					var msgId=msg.dataset.msgId||'';
					if(!msgId){
						var prev=null;
						document.querySelectorAll('#ocCI .ocMsg').forEach(function(m){
							if(m.querySelector('.ocMbb')===bb){
								var allMsgs=rMsgs._lastMsgs||[];
								for(var i=0;i<allMsgs.length;i++){
									if(allMsgs[i].role==='user'&&allMsgs[i].content===oldText){
										msgId=allMsgs[i].id;break;
									}
								}
							}
						});
					}
					api('edit_message',{message_id:msgId,content:newText},function(e,r){
						if(!e&&r&&r.success){
							while(msg.nextSibling)msg.parentNode.removeChild(msg.nextSibling);
							bb.textContent=newText;
							inp=document.getElementById('ocIn');
							inp.value=newText;
							origSend();
							toast('Message edited and resent','ok');
						}else{
							toast('Failed to edit message','err');
						}
					});
				}
			});
		});
		act.appendChild(editBtn);
	}
	if(isLast&&!isU){
		var regenBtn=document.createElement('button');
		regenBtn.className='ocMsgActB';
		regenBtn.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" height="12" viewBox="0 -960 960 960" width="12" fill="currentColor"><path d="M396-200q-97 0-166.5-63T160-420q0-94 69.5-157T396-640h252L544-744l56-56 200 200-200 200-56-56 104-104H396q-63 0-109.5 40T240-420q0 60 46.5 100T396-280h284v80H396Z"/></svg>';
		regenBtn.title='Regenerate';
		regenBtn.addEventListener('click',function(ev){
			ev.stopPropagation();
			api('regenerate',{},function(e,r){
				if(!e&&r&&r.success){
					msg.parentNode.removeChild(msg);
					toast('Regenerating...','info');
					var lastUserMsg='';
					document.querySelectorAll('#ocCI .ocMsg.u').forEach(function(m){
						lastUserMsg=m.querySelector('.ocMbb').textContent||'';
					});
					if(lastUserMsg){
						var inp=document.getElementById('ocIn');
						inp.value=lastUserMsg;
						origSend();
						inp.value='';
					}
				}
			});
		});
		act.appendChild(regenBtn);
	}
	var copyBtn=document.createElement('button');
	copyBtn.className='ocMsgActB';
	copyBtn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
	copyBtn.title='Copy';
	copyBtn.addEventListener('click',function(ev){
		ev.stopPropagation();
		var bb=msg.querySelector('.ocMbb');
		if(!bb)return;
		var txt=bb.innerText||bb.textContent;
		var ta=document.createElement('textarea');ta.value=txt;ta.style.cssText='position:fixed;left:-9999px';document.body.appendChild(ta);ta.select();
		try{document.execCommand('copy')}catch(e){}
		document.body.removeChild(ta);
		toast('Copied','ok');
	});
	act.appendChild(copyBtn);
	msg.appendChild(act);
});

// Message timestamps on hover
var _origRMsg=rMsg;
rMsg=function(m){
	var bb=_origRMsg(m);
	var msgEl=bb.closest('.ocMsg');
	if(msgEl&&m.time){
		var ts=document.createElement('span');
		ts.className='ocMsgTs';
		var dt=new Date(m.time*1000);
		ts.textContent=dt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
		msgEl.appendChild(ts);
	}
	if(m.id)msgEl.dataset.msgId=m.id;
	return bb;
};

// Store last loaded messages for edit lookup
var _origRMsgs=rMsgs;
rMsgs=function(msgs){
	rMsgs._lastMsgs=msgs||[];
	_origRMsgs(msgs);
};

// Conversation loading spinner
var _origLoadC=loadC;
loadC=function(id){
	aC=id;
	updateURL(id);
	var ch=document.getElementById('ocCI');
	ch.innerHTML='<div class="ocLoad"><span class="ocLoadD"></span><span class="ocLoadD"></span><span class="ocLoadD"></span><span>Loading...</span></div>';
	var u=A+'&ai_php_api=conversation&csrf_token='+C+'&conversation_id='+encodeURIComponent(id);
	var x=new XMLHttpRequest();
	x.open('GET',u,true);
	x.setRequestHeader('Accept','application/json');
	x.onload=function(){
		if(x.status===200){
			try{
				var d=JSON.parse(x.responseText);
				rMsgs(d.messages||[]);
				hlS(id);
			}catch(e){}
		}
	};
	x.send();
};

// Auto-title after first AI response
var _origXhrOnload;
var origDoSendPatched=false;

// File tree search
var ftSearchEl=document.getElementById('ocFtSearch');
if(ftSearchEl){
	ftSearchEl.addEventListener('input',function(){
		var q=this.value.toLowerCase();
		var items=document.querySelectorAll('#ocFtL .ocFtI');
		items.forEach(function(it){
			var name=it.querySelector('.ocFtIn');
			if(!name)return;
			it.style.display=(!q||name.textContent.toLowerCase().indexOf(q)!==-1)?'':'none';
		});
	});
}

// Changes panel
var chgOpen=false;
document.getElementById('ocChgBtn').addEventListener('click',function(){
	chgOpen=!chgOpen;
	var panel=document.getElementById('ocChg');
	if(chgOpen){
		api('changes',null,function(e,d){
			if(e||!d){toast('Failed to load changes','err');return}
			var list=document.getElementById('ocChgL');
			list.innerHTML='';
			var snaps=d.snapshots||[];
			if(!snaps.length){
				list.innerHTML='<div style="padding:14px;color:var(--t3);font-size:12px;text-align:center">No changes yet</div>';
			}else{
				snaps.forEach(function(s){
					var item=document.createElement('div');
					item.className='ocChgI';
					var dt=new Date((s.time||0)*1000);
					var ts=dt.toLocaleDateString()===new Date().toLocaleDateString()
						?dt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})
						:dt.toLocaleDateString([],{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
					item.innerHTML='<span>'+esc(s.message||'Snapshot')+' <span style="color:var(--t3);font-size:10px">'+ts+'</span></span>'
						+'<button class="ocChgBtn" data-id="'+esc(s.id||'')+'">Restore</button>';
					list.appendChild(item);
				});
				list.querySelectorAll('.ocChgBtn').forEach(function(btn){
					btn.addEventListener('click',function(){
						if(confirm('Restore this snapshot?')){
							api('restore',{id:this.dataset.id},function(e,r){
								if(!e&&r&&r.restored)toast('Snapshot restored','ok');
								else toast('Failed to restore','err');
							});
						}
					});
				});
			}
		});
	}
	panel.classList.toggle('open',chgOpen);
});
document.getElementById('ocChgC').addEventListener('click',function(){
	chgOpen=false;
	document.getElementById('ocChg').classList.remove('open');
});

// Auto-title generation after streaming
var _origSendRef=origSend;
(function(){
	var origOnload=null;
	var patchSend=function(){
		var origSendBtn=document.getElementById('ocSend');
		var clickHandler=function(){
			setTimeout(function(){
				if(aC){
					setTimeout(function(){
						api('auto_title',{},function(e,r){
							if(!e&&r&&r.title){
								loadSL();
							}
						});
					},2000);
				}
			},100);
		};
		origSendBtn.addEventListener('click',clickHandler);
	};
	patchSend();
})();

// Make send reference the correct function for global usage

// Project Management
function loadProjects(){
	api('projects_list',null,function(e,d){
		if(e||!d)return;
		projects=d;
		renderProjectList('');
		if(typeof loadSidebarProjects==='function') loadSidebarProjects();
	});
	api('projects_wordpress',null,function(e,d){
		if(e||!d)return;
		wordpressInstalls=d;
		var sel=document.getElementById('ocProjWpSel');
		if(sel){
			sel.innerHTML='<option value="">Select installation...</option>';
			d.forEach(function(w){
				var o=document.createElement('option');
				o.value=w.insid;
				o.textContent=w.name+' - '+w.path;
				sel.appendChild(o);
			});
		}
	});
}

function renderProjectList(query){
	var list=document.getElementById('ocProjList');
	if(!list)return;
	list.innerHTML='';
	if(!projects.length){
		list.innerHTML='<div style="padding:20px;text-align:center;color:var(--t3);font-size:13px">No projects yet. Create one to get started!</div>';
		return;
	}
	var q=(query||'').toLowerCase();
	var filtered=projects.filter(function(p){
		return !q||(p.name||'').toLowerCase().indexOf(q)!==-1||(p.path||'').toLowerCase().indexOf(q)!==-1;
	});
	if(!filtered.length){
		list.innerHTML='<div style="padding:20px;text-align:center;color:var(--t3);font-size:13px">No projects match your search.</div>';
		return;
	}
	filtered.forEach(function(p){
		var item=document.createElement('div');
		item.className='ocPi';
		item.dataset.id=p.project_id;
		item.style.cursor='pointer';
		item.innerHTML='<span class="ocPiN">'+esc(p.name||'Untitled')+'<span class="ocPiSub">'+esc(p.path||'')+'</span></span>'
			+'<button class="ocB ocBs" data-del="1" style="font-size:10px;padding:2px 8px;margin-left:8px">Delete</button>';
		item.addEventListener('click',function(ev){
			if(ev.target.dataset.del)return;
			switchToProject(p.project_id);
		});
		item.querySelector('[data-del]').addEventListener('click',function(ev){
			ev.stopPropagation();
			if(confirm('Delete project "'+(p.name||p.path)+'"? This will not delete the actual directory.')){
				api('projects_delete',{project_id:p.project_id},function(e,r){
					if(!e&&r&&r.success){
						loadProjects();
						toast('Project deleted','ok');
					}else{
						toast('Failed to delete project','err');
					}
				});
			}
		});
		list.appendChild(item);
	});
}

function switchToProject(projectId){
	var u=new URL(window.location);
	u.searchParams.set('project_id',projectId);
	u.searchParams.delete('insid');
	u.searchParams.delete('path');
	u.searchParams.delete('session');
	window.location.href=u.toString();
}

function openProjectModal(){
	document.getElementById('ocProjMod').classList.add('open');
	document.getElementById('ocProjSearch').value='';
	document.getElementById('ocProjNewForm').style.display='none';
	loadProjects();
}

function closeProjectModal(){
	document.getElementById('ocProjMod').classList.remove('open');
}

document.getElementById('ocProjBtn').addEventListener('click',openProjectModal);
document.getElementById('ocProjC').addEventListener('click',closeProjectModal);
document.getElementById('ocProjMod').addEventListener('click',function(e){
	if(e.target===this)closeProjectModal();
});

// Help FAQ Popup
var faqs=[
{q:'What is the AI Assistant?',a:'The AI Assistant is a coding companion integrated into Softaculous that helps you write, debug, refactor, and understand code in your projects. It can read and write files, run shell commands, and search your codebase.'},
{q:'How do I get started?',a:'1. Create a project by clicking "Projects" then "+ New Project". You can select an existing WordPress installation or enter a custom directory path.\n2. Choose an AI model from the dropdown in the header (you need to connect a provider in Settings first).\n3. Type your request in the chat input and press Enter.\n4. The AI will respond and can perform actions like editing files or running commands.'},
{q:'What are Build and Plan modes?',a:'Build Mode: The AI has full access to read, write, edit files and run commands. Use this when you want the AI to make changes.\nPlan Mode: The AI operates in read-only mode - it can explore and analyze your code but cannot modify anything. Use this for code reviews, explanations, and planning changes before implementing them.'},
{q:'How do I connect an AI provider?',a:'1. Click the Settings button in the header.\n2. Find the provider you want to use and click "Connect".\n3. Enter your API key and click "Connect".\n4. You can use the "Test" button to verify your connection works.\n\nFree models are available through "OpenCode Zen (Free)" which requires no API key.'},
{q:'What AI providers are supported?',a:'OpenAI (GPT-4o, GPT-5), Anthropic (Claude), Google (Gemini), DeepSeek, Groq, Together AI, OpenRouter, Ollama (local), MiniMax, and custom OpenAI-compatible providers.'},
{q:'How do projects work?',a:'A project is linked to a directory on your server. The AI Assistant works within that project directory. Each project has its own chat sessions, conversation history, and file tree. You can switch between projects using the dropdown in the header, next to the sidebar toggle button.'},
{q:'How do sessions work?',a:'Each project can have multiple chat sessions. Click "+ New Session" to start a fresh conversation. Previous sessions are listed in the sidebar and can be clicked to resume. You can delete sessions with the × button.'},
{q:'What can the AI do?',a:'Read and write files, edit existing files with targeted changes, search files by name or content, run shell commands, create directory listings, and more. In Build mode it can make all these changes. In Plan mode it can only read and search.'},
{q:'How do I edit and resend a message?',a:'Hover over any user message and click the pencil icon. Edit the text inline and click "Send". The conversation will be truncated from that point and the AI will respond to your edited message.'},
{q:'What are the keyboard shortcuts?',a:'Escape: Stop streaming / Close modals\nEnter: Send message (Shift+Enter for new line)\nCtrl+Shift+N: New session\nCtrl+Shift+P: Plan mode\nCtrl+,: Open Settings'},
{q:'What are slash commands?',a:'Type / in the chat input to see available commands:\n/build - Switch to Build mode\n/plan - Switch to Plan mode\n/clear - Clear current conversation\n/export - Download conversation as Markdown\n/help - Show keyboard shortcuts'}
];
document.getElementById('ocHlBtn').addEventListener('click',function(){
	var bd=document.getElementById('ocHelpBd');
	bd.innerHTML='';
	faqs.forEach(function(f){
		var item=document.createElement('div');
		item.className='ocFaqI';
		item.innerHTML='<div class="ocFaqQ">'+esc(f.q)+'</div><div class="ocFaqA">'+md(f.a)+'</div>';
		item.querySelector('.ocFaqQ').addEventListener('click',function(){
			var wasOpen=item.classList.contains('open');
			bd.querySelectorAll('.ocFaqI.open').forEach(function(el){el.classList.remove('open')});
			if(!wasOpen) item.classList.add('open');
		});
		bd.appendChild(item);
	});
	document.getElementById('ocHelpMod').classList.add('open');
});
document.getElementById('ocHelpC').addEventListener('click',function(){
	document.getElementById('ocHelpMod').classList.remove('open');
});
document.getElementById('ocHelpMod').addEventListener('click',function(e){
	if(e.target===this)this.classList.remove('open');
});
document.getElementById('ocProjSearch').addEventListener('input',function(){
	renderProjectList(this.value);
});
document.getElementById('ocProjNewBtn').addEventListener('click',function(){
	document.getElementById('ocProjNewForm').style.display='block';
	document.getElementById('ocProjName').value='';
	document.getElementById('ocProjPath').value='';
	document.getElementById('ocProjWpSel').value='';
	if(wordpressInstalls&&wordpressInstalls.length){
		document.getElementById('ocProjWpSection').style.display='block';
	}else{
		document.getElementById('ocProjWpSection').style.display='none';
	}
});
document.getElementById('ocProjCan').addEventListener('click',function(){
	document.getElementById('ocProjNewForm').style.display='none';
});
document.getElementById('ocProjWpSel').addEventListener('change',function(){
	if(this.value){
		var wp=wordpressInstalls.find(function(w){return w.insid===this.value},this);
		if(wp){
			var relPath=wp.path;
			if(relPath.indexOf(HD)===0 && HD.length>0) relPath=relPath.substring(HD.length+1);
			document.getElementById('ocProjPath').value=relPath;
			if(!document.getElementById('ocProjName').value){
				document.getElementById('ocProjName').value=wp.name;
			}
		}
	}
});
document.getElementById('ocProjCre').addEventListener('click',function(){
	var name=document.getElementById('ocProjName').value.trim();
	var path=document.getElementById('ocProjPath').value.trim();
	var insid=document.getElementById('ocProjWpSel').value;
	var fullPath=path;
	if(path.indexOf('/')!==0){
		fullPath=HD+'/'+path;
	}
	var type='custom';
	if(insid){
		type='wordpress';
	}
	api('projects_create',{name:name,path:fullPath,type:type,insid:insid},function(e,r){
		if(!e&&r&&!r.error){
			closeProjectModal();
			if(r.project_id){
				switchToProject(r.project_id);
			}else{
				toast('Project created but failed to switch','warn');
				loadProjects();
			}
		}else{
			toast('Failed to create project: '+(r&&r.error||e),'err');
		}
	});
});

// If no softpath, auto-open project modal
if(!SP){
	setTimeout(openProjectModal,500);
}

})();

</script>
</body>
</html>
<?php
}

function ai_select_theme(){
	global $user, $globals, $l, $theme, $softpanel, $error, $iscripts;

	$username = (!empty($softpanel->user['name']) ? $softpanel->user['name'] : (!empty($user['username']) ? $user['username'] : ''));
	$home_dir = !empty($softpanel->user['homedir']) ? $softpanel->user['homedir'] : (function_exists('ai_get_homedir') ? ai_get_homedir($username) : '/home/' . $username);

	$installations = array();
	if(!empty($user['ins'])){
		foreach($user['ins'] as $insid => $data){
			$soft = get_sid_by_version($data['ver'], $data['sid']);
			$software = !empty($iscripts[$soft]) ? $iscripts[$soft] : array('name' => 'Software');
			$installations[] = array('insid' => $insid, 'name' => $software['name'], 'url' => !empty($data['softurl']) ? $data['softurl'] : '', 'path' => $data['softpath']);
		}
	}

	ob_start();
	softheader(__('$0 - Code with AI', array(APP)));
	ob_end_clean();

	echo '<style>
		body { background: #F9FAFB !important; }
		.ai-select-container { max-width: 800px; margin: 40px auto; padding: 0; background: transparent; border: none; box-shadow: none; }
		.ai-select-header { background: #fff; border-radius: 12px 12px 0 0; padding: 24px 30px; border-bottom: 1px solid #e1e8ed; }
		.ai-select-title { font-size: 24px; font-weight: 500; color: #1a1a2e; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
		.ai-select-title i { color: #6c5ce7; font-size: 24px; }
		.ai-select-subtitle { font-size: 14px; color: #6f6f6f; margin: 0; }
		.ai-select-alert { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px 16px; margin: 20px 0 0; font-size: 13px; color: #856404; display: flex; align-items: center; gap: 10px; }
		.ai-select-alert i { color: #ffc107; }
		.ai-custom-section { background: #fff; border-radius: 12px; padding: 24px 30px; margin-bottom: 20px; border: 1px solid #e1e8ed; }
		.ai-custom-title { font-size: 16px; font-weight: 500; color: #1a1a2e; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
		.ai-custom-title i { color: #6c5ce7; }
		.ai-custom-desc { font-size: 13px; color: #6f6f6f; margin-bottom: 16px; }
		.ai-custom-form { display: flex; gap: 10px; align-items: center; }
		.ai-custom-home { font-size: 13px; color: #8f8f8f; background: #f8f9fa; padding: 10px 12px; border: 1px solid #e1e8ed; border-right: none; border-radius: 6px 0 0 6px; font-family: monospace; white-space: nowrap; }
		.ai-custom-input { flex: 1; padding: 10px 14px; border: 1px solid #e1e8ed; border-radius: 0 6px 6px 0; background: #fff; color: #1a1a2e; font-size: 14px; font-family: monospace; }
		.ai-custom-input:focus { outline: none; border-color: #6c5ce7; }
		.ai-custom-btn { padding: 10px 20px; background: #6c5ce7; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; white-space: nowrap; }
		.ai-custom-btn:hover { background: #5a4ad1; }
		.ai-or-divider { text-align: center; margin: 20px 0; color: #8f8f8f; font-size: 13px; position: relative; }
		.ai-or-divider::before, .ai-or-divider::after { content: ""; position: absolute; top: 50%; width: 40%; height: 1px; background: #e1e8ed; }
		.ai-or-divider::before { left: 0; }
		.ai-or-divider::after { right: 0; }
		.ai-installs-section { background: #fff; border-radius: 12px; padding: 24px 30px; border: 1px solid #e1e8ed; }
		.ai-installs-header { font-weight: 500; color: #6f6f6f; margin-bottom: 16px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
		.ai-installs-header i { color: #6c5ce7; }
		.ai-install-list { display: flex; flex-direction: column; gap: 10px; }
		.ai-install-item { display: flex; align-items: center; padding: 14px 16px; border: 1px solid #e1e8ed; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit; }
		.ai-install-item:hover { border-color: #6c5ce7; background: #fafafe; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(108,92,231,0.1); }
		.ai-install-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; margin-right: 14px; font-size: 16px; flex-shrink: 0; }
		.ai-install-info { flex: 1; min-width: 0; }
		.ai-install-name { font-weight: 500; color: #1a1a2e; margin-bottom: 4px; font-size: 14px; }
		.ai-install-url { font-size: 12px; color: #6c5ce7; margin-bottom: 2px; word-break: break-all; }
		.ai-install-path { font-size: 11px; color: #8f8f8f; font-family: monospace; }
		.ai-install-arrow { color: #ccc; font-size: 14px; margin-left: 10px; }
	</style>';

	echo '<div class="ai-select-container">
		<div class="ai-select-header">
			<div class="ai-select-title">
				<i class="fas fa-robot"></i> Code with AI
			</div>
			<div class="ai-select-subtitle">Select a project to start coding with AI</div>
		</div>

		<div class="ai-custom-section">
			<div class="ai-custom-title">
				<i class="fas fa-folder-open"></i> Open Custom Path
			</div>
			<div class="ai-custom-desc">Enter a directory path to work with AI on any project</div>
			<form method="get" action="'.$globals['index'].'">
				<input type="hidden" name="act" value="ai"/>
				<div class="ai-custom-form">
					<span class="ai-custom-home">'.$home_dir.'/</span>
					<input type="text" name="path" class="ai-custom-input" placeholder="public_html/project" />
					<button type="submit" class="ai-custom-btn">Open</button>
				</div>
			</form>
		</div>';

	if(!empty($installations)){
		echo '<div class="ai-or-divider">OR select from your installations</div>

		<div class="ai-installs-section">
			<div class="ai-installs-header">
				<i class="fas fa-globe"></i> Your Installations
			</div>
			<div class="ai-install-list">';

		foreach($installations as $ins){
			echo '<a href="'.$globals['ind'].'act=ai&insid='.$ins['insid'].'" class="ai-install-item">
				<div class="ai-install-icon"><i class="fas fa-globe"></i></div>
				<div class="ai-install-info">
					<div class="ai-install-name">'.htmlspecialchars($ins['name']).'</div>
					<div class="ai-install-url">'.htmlspecialchars($ins['url']).'</div>
					<div class="ai-install-path">'.htmlspecialchars($ins['path']).'</div>
				</div>
				<div class="ai-install-arrow"><i class="fas fa-chevron-right"></i></div>
			</a>';
		}
		echo '</div></div>';
	}

	echo '</div>';
	die();
}
