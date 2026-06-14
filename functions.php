<?php
//FILESIZES
@ini_set( 'upload_max_size' , '512M' );
@ini_set( 'post_max_size', '512M');
@ini_set( 'max_execution_time', '600' );

add_filter( 'use_block_editor_for_post', '__return_false' );