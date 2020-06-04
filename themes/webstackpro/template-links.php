<?php
/*
Template Name: 友情链接
*/
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); 

include( get_theme_file_path('/templates/sidebar-nav.php') ); ?>
<style type="text/css"> 
h3{font-size: 1.1rem;margin-top: 20px}
.contextual-callout  p{font-size: 13px;}
.contextual-callout {background-color: rgba(241, 64, 75, 0.12);color: #f1404b!important;padding: 15px;margin: 10px 0 20px;border: 1px solid rgba(241, 64, 75, 0.07);border-left-width: 4px;border-radius: 3px;font-size: 1.3rem;line-height: 1.5;border-left-color: #f1404b;}
.contextual-callout>h4 {margin-bottom: 16px;text-align: center;font-size: 1rem;color:#f1404b}
.link-header h1 {font-size: 16px;font-size: 1.6rem;line-height: 30px;text-align: center;margin: 0 0 15px 0;}
.link-page {margin: 30px 0; } 
</style>
<div class="main-content flex-fill">
    
	<?php get_template_part( 'templates/header','banner' ); ?>
	<div id="content" class="container my-4 my-md-5">
			<div class="content-area">
				<main class="site-main">
				<?php while ( have_posts() ) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>"  class="type-post post">
					<h1 class="h2 mb-4"><?php echo get_the_title() ?></h1>
						<div class="content page">
							<div class="single-content">
								<p>一、申请友链可以直接在本页面留言，内容包括网站名称、链接以及相关说明，请先做好本站链接并此处留言，将尽快处理。<p>
								<p>二、欢迎申请友情链接，首页和内页链接需符合以下要求：<p>
								<ul>
									<li>已备案网站；</li>
									<li>能够正常维护更新的网站;</li>
									<li>优先考虑垂直领域网站；</li>
								</ul>
								<p>长期不更新或友链检测失效的网站，将暂时关闭链接，若有疑问请联系214180667@qq.com。</p>								<div class="contextual-callout">
									<h4>友链申请示例</h4>
									<p>本站名称：<?php echo get_bloginfo('name') ?><br>
									本站链接：<?php echo home_url() ?><br>
									本站描述：<?php echo get_bloginfo('description') ?></p>
								</div>
								<?php the_content(); ?>
								<?php edit_post_link(__('编辑','i_swallow'), '<span class="edit-link">', '</span>' ); ?>
							</div> <!-- .single-content -->  
          					<article class="link-page">
						 
								<?php $default_ico = get_template_directory_uri() .'/images/favicon.png'; 
								$linkcats = get_terms( 'link_category' );
								if ( !empty( $linkcats ) ) {
									foreach( $linkcats as $linkcat ){
										echo '<div class="link-title mb-3"><h3 class="link-cat"><i class="site-tag iconfont icon-tag icon-lg mr-1"></i>'.$linkcat->name.'</h3></div>';
										$bookmarks = get_bookmarks(array(
											'orderby' => 'rating',
											'order' => 'asc',
											'category'  => $linkcat->term_id,
										));
										echo'<div class="row">';
									  	foreach ($bookmarks as $bookmark) { 
											$ico = io_get_option('ico-source')['ico_url'] .format_url($bookmark->link_url) . io_get_option('ico-source')['ico_png'];
										?>
										<div class="url-card col-6 col-md-3"> 
										  	<div class="card url-body default">    
							                	<div class="card-body">
													<div class="url-content d-flex align-items-center"> 
                									    <div class="url-img rounded-circle mr-2 d-flex align-items-center justify-content-center">
                									        <?php if(io_get_option('lazyload')): ?>
                									        <img class="lazy" src="<?php echo $default_ico; ?>" data-src="<?php echo $ico ?>" onerror="javascript:this.src='<?php echo $default_ico; ?>'">
                									        <?php else: ?>
                									        <img class="" src="<?php echo $ico ?>" onerror="javascript:this.src='<?php echo $default_ico; ?>'">
                									        <?php endif ?>
                									    </div> 
                									    <div class="url-info flex-fill">
                									        <div class="text-sm overflowClip_1">
																<a href="<?php echo $bookmark->link_url; ?>" title="<?php echo $bookmark->link_name; ?>" target="_blank"><strong><?php echo $bookmark->link_name; ?></strong></a>
                									        </div>
                									        <p class="overflowClip_1 m-0 text-xs"><?php echo $bookmark->link_description ?></p>
                									    </div>
                									</div>
                								</div>
                							</div>
                						</div>
								  		<?php }
										echo'</div>';
									}
								} else {
        							echo'<div class="row">';
          		  					$bookmarks = get_bookmarks(array(
          		    					'orderby' => 'rating',
          		    					'order' => 'asc'
          		  					));
									foreach ($bookmarks as $bookmark) { 
										$ico = io_get_option('ico-source')['ico_url'] .format_url($bookmark->link_url) . io_get_option('ico-source')['ico_png'];
									?>
										<div class="url-card col-6 col-md-3"> 
										  	<div class="card url-body default">    
							                	<div class="card-body">
													<div class="url-content d-flex align-items-center"> 
                									    <div class="url-img rounded-circle mr-2 d-flex align-items-center justify-content-center">
                									        <?php if(io_get_option('lazyload')): ?>
                									        <img class="lazy" src="<?php echo $default_ico; ?>" data-src="<?php echo $ico ?>" onerror="javascript:this.src='<?php echo $default_ico; ?>'">
                									        <?php else: ?>
                									        <img class="" src="<?php echo $ico ?>" onerror="javascript:this.src='<?php echo $default_ico; ?>'">
                									        <?php endif ?>
                									    </div> 
                									    <div class="url-info flex-fill">
                									        <div class="text-sm overflowClip_1">
																<a href="<?php echo $bookmark->link_url; ?>" title="<?php echo $bookmark->link_name; ?>" target="_blank"><strong><?php echo $bookmark->link_name; ?></strong></a>
                									        </div>
                									        <p class="overflowClip_1 m-0 text-xs"><?php echo $bookmark->link_description ?></p>
                									    </div>
                									</div>
                								</div>
                							</div>
                						</div>
									<?php }
									echo'</div>';
								} ?>
								<div class="clear"></div>
							</article>   
						</div><!-- .content -->
					</article><!-- #page -->
					<?php endwhile; ?>
					<?php while ( have_posts() ) : the_post(); ?>
					<?php if ( comments_open() || get_comments_number() ) : ?>
					<?php comments_template( '', true ); ?>
					<?php endif; ?>
					<?php endwhile; ?>
				</main>      
			</div>     
			</div>

<?php get_footer();
