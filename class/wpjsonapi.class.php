<?php

class wpJsonApi {

    /* DEFAULT FIELDS */
    private $fields = array(
        'id',
        'slug',
        'title',
        'excerpt',
        'content',
        'date',
        'permalink',
        'status',
    );

    /* SET NEW FIELDS AND CALL LOOP */
    function __construct( $options = array() ) {
        header('Content-Type: application/json; charset=utf-8');
        
        $this->fields = array_merge( $this->fields, $options['fields'] );
        if( isset( $options['query'] ) ){
            $this->queryLoop($options['query']);
        }
        else{
            $this->defaultLoop();
        }
    }

    /* RUN THE DEFAULT LOOP */
    private function defaultLoop(){
        $arr = array( 'posts' => array() );
        if( have_posts() ): while( have_posts() ): the_post();
            array_push($arr['posts'], $this->getPostData());
        endwhile; endif; wp_reset_postdata();
        $this->runJson($arr);
    }

    /* RUN THE QUERY LOOP */
    private function queryLoop($query){
        $arr = array( 'posts' => array() );
        $wpQuery = new WP_Query( $query );
        if($wpQuery->have_posts()) : while($wpQuery->have_posts()) : $wpQuery->the_post();
            array_push($arr['posts'], $this->getPostData());
        endwhile; endif; wp_reset_postdata();
        $this->runJson($arr, $wpQuery);
    }

    /* RETURN A SINGLE POST DATA ARRAY */
    private function getPostData(){
        $post = new stdClass();

        foreach( $this->fields as $field ){
            $res = $this->getPostField($field);
            $post->$field = $res[1];
        }
        return $post;
    }

    /* RETURN A SINGLE FIELD DATA ARRAY OF A SINGLE POST */
    private function getPostField($field){
        switch($field){
            case 'id':
                return array( $field, get_the_ID() );
                break;
            case 'title':
                return array( $field, get_the_title() );
                break;
            case 'slug':
                return array( $field, get_post_field( 'post_name', get_post() ) );
                break;
            case 'excerpt':
                return array( $field, get_the_excerpt() );
                break;
            case 'content':
                return array( $field, get_the_content() );
                break;
            case 'date':
                return array( $field, get_the_date() );
                break;
            case 'permalink':
                return array( $field, get_the_permalink() );
                break;
            case 'status':
                return array( $field, get_post_status() );
                break;
            default:
                return array( $field, get_field($field) );
        }
    }

    /* ADD ADDITIONAL INFORMATION AND PRINT JSON */
    private function runJson($arr, $wpQuery){
        $arr['pagination'] = array(
            'paged' => $wpQuery->query['paged'],
            'posts_per_page' => $wpQuery->query['posts_per_page'],
            'found_posts' => $wpQuery->found_posts,
        );

        $json = json_encode($arr);
        echo $json;
    }
}