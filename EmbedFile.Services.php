<?php

// Build services list (may be augmented in LocalSettings.php)
// $1 ScriptPath, $2 url, $3 width, $4 height, $5 description
$wgEmbedFileServiceList = array(
    'mp4' => array(
		'extern' => '<div class="thumbnails embed"><div><video src="$2" style="max-width:$3px;" controls></video></div><div class="description">$5</div></div>',		
	),
    'webm' => array(
                'extern' => '<div class="thumbnails embed"><div><video src="$2" style="max-width:$3px;" controls></video></div><div class="description">$5</div></div>',
        ),
    'ogv' => array(
                'extern' => '<div class="thumbnails embed"><div><video src="$2" style="max-width:$3px;" controls></video></div><div class="description">$5</div></div>',
        ),
    'ogg' => array(
                'extern' => '<div class="thumbnails embed"><div><video src="$2" style="max-width:$3px;" controls></video></div><div class="description">$5</div></div>',
        ),
    'mp3' => array(
                'extern' => '<div class="thumbnails embed"><div><video src="$2" style="max-width:$3px;" controls></video></div><div class="description">$5</div></div>',
        ),
    'wav' => array(
                'extern' => '<div class="thumbnails embed"><div><video src="$2" style="max-width:$3px;" controls></video></div><div class="description">$5</div></div>',
        ),


    'jpeg' => array(
                'extern' => '<ul class="thumbnails embed"><li><div class="thumbnail"><a class="img-link" href="$2" target="_blank"><img src="$2" style="width:$3px; max-width: 100%;"/></a><div class="caption" style="text-align:center;">$5</div></div></li></ul>',
        ),
    'jpg' => array(
		'extern' => '<ul class="thumbnails embed"><li style="width:$3px;"><div class="thumbnail"><a class="img-link" href="$2" target="_blank"><img src="$2" style="max-width: 100%;"/></a><div class="caption" style="text-align:center;">$5</div></div></li></ul>',
	),
    'png' => array(
		'extern' => '<ul class="thumbnails embed"><li><div class="thumbnail"><a class="img-link" href="$2" target="_blank"><img src="$2" style="width:$3px; max-width: 100%;"/></a><div class="caption" style="text-align:center;">$5</div></div></li></ul>',
        ),
    'svg' => array(
                'extern' => '<ul class="thumbnails embed"><li><div class="thumbnail"><a class="img-link" href="$2" target="_blank"><img src="$2" style="width:$3px; max-width: 100%;"/></a><div class="caption" style="text-align:center;">$5</div></div></li></ul>',
        ),
    'bmp' => array(
                'extern' => '<ul class="thumbnails embed"><li><div class="thumbnail"><a class="img-link" href="$2" target="_blank"><img src="$2" style="width:$3px; max-width: 100%;"/></a><div class="caption" style="text-align:center;">$5</div></div></li></ul>',
        ),




);
