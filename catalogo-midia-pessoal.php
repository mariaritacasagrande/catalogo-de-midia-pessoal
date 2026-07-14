<?php
/**
 * Plugin Name: Catálogo de Mídia Pessoal
 * Plugin URI: https://casagrande.dev
 * Description: Cadastre filmes, livros e séries como posts convencionais (usando os templates padrão do seu tema), avalie com nota e sentimento final, e exiba na sidebar o que está sendo lido/assistido no momento.
 * Version: 1.0.0
 * Author: Seu Site
 * Text Domain: catalogo-midia-pessoal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acesso direto negado.
}

define( 'CMP_VERSION', '1.0.0' );
define( 'CMP_POST_TYPE', 'cmp_midia' );
define( 'CMP_TAXONOMY', 'cmp_tipo' );

/**
 * ===========================================================
 * 1. REGISTRO DO CUSTOM POST TYPE 
 * ===========================================================

 */
function cmp_registrar_tamanho_imagem() {
	// Tamanho retrato (estilo capa de livro / pôster) usado no widget de sidebar.
	add_image_size( 'cmp_capa_retrato', 60, 90, true );
}
add_action( 'after_setup_theme', 'cmp_registrar_tamanho_imagem' );

function cmp_register_post_type() {
	$labels = array(
		'name'                  => 'Catálogo (Filmes, Livros e Séries)',
		'singular_name'         => 'Item do Catálogo',
		'menu_name'             => 'Catálogo de Mídia',
		'add_new'               => 'Adicionar Novo',
		'add_new_item'          => 'Adicionar Novo Item',
		'edit_item'             => 'Editar Item',
		'new_item'              => 'Novo Item',
		'view_item'             => 'Ver Item',
		'view_items'            => 'Ver Itens',
		'search_items'          => 'Buscar no Catálogo',
		'not_found'             => 'Nenhum item encontrado',
		'not_found_in_trash'    => 'Nenhum item na lixeira',
		'all_items'             => 'Todos os Itens',
		'archives'              => 'Arquivo do Catálogo',
		'featured_image'        => 'Capa',
		'set_featured_image'    => 'Definir capa',
		'remove_featured_image' => 'Remover capa',
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_nav_menus'  => true,
		'show_in_admin_bar'  => true,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'catalogo' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-book-alt',
		'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author' ),

	);

	register_post_type( CMP_POST_TYPE, $args );
}
add_action( 'init', 'cmp_register_post_type' );

/**
 * ===========================================================
 * 2. TAXONOMIA: Tipo de Mídia (Filme, Livro, Série)
 * ===========================================================
 */
function cmp_register_taxonomy() {
	$labels = array(
		'name'          => 'Tipos de Mídia',
		'singular_name' => 'Tipo de Mídia',
		'search_items'  => 'Buscar Tipos',
		'all_items'     => 'Todos os Tipos',
		'edit_item'     => 'Editar Tipo',
		'update_item'   => 'Atualizar Tipo',
		'add_new_item'  => 'Adicionar Novo Tipo',
		'new_item_name' => 'Nome do Novo Tipo',
		'menu_name'     => 'Tipo',
	);

	register_taxonomy(
		CMP_TAXONOMY,
		array( CMP_POST_TYPE ),
		array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'tipo-midia' ),
		)
	);
}
add_action( 'init', 'cmp_register_taxonomy' );

/**
 * Cria os termos padrão (Filme, Livro, Série) na ativação do plugin.
 */
function cmp_activate_plugin() {
	cmp_register_post_type();
	cmp_register_taxonomy();

	$termos_padrao = array( 'Filme', 'Livro', 'Série' );
	foreach ( $termos_padrao as $termo ) {
		if ( ! term_exists( $termo, CMP_TAXONOMY ) ) {
			wp_insert_term( $termo, CMP_TAXONOMY );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cmp_activate_plugin' );

function cmp_deactivate_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cmp_deactivate_plugin' );

/**
 * ===========================================================
 * 3. META BOX: Avaliação (nota, sentimento, status, datas)
 * ===========================================================
 */
function cmp_add_meta_boxes() {
	add_meta_box(
		'cmp_avaliacao_box',
		'Avaliação e Progresso',
		'cmp_render_meta_box',
		CMP_POST_TYPE,
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'cmp_add_meta_boxes' );

function cmp_get_sentimentos() {
	return array(
		''            => 'Selecione…',
		'adorei'      => '😍 Adorei',
		'gostei'      => '🙂 Gostei',
		'neutro'      => '😐 Neutro',
		'nao_gostei'  => '🙁 Não gostei',
		'detestei'    => '😡 Detestei',
	);
}

function cmp_get_status() {
	return array(
		'quero'      => 'Quero ver/ler',
		'andamento'  => 'Em andamento',
		'concluido'  => 'Concluído',
		'abandonado' => 'Abandonado',
	);
}

function cmp_render_meta_box( $post ) {
	wp_nonce_field( 'cmp_salvar_avaliacao', 'cmp_avaliacao_nonce' );

	$nota        = get_post_meta( $post->ID, '_cmp_nota', true );
	$sentimento  = get_post_meta( $post->ID, '_cmp_sentimento', true );
	$status      = get_post_meta( $post->ID, '_cmp_status', true );
	$data_inicio = get_post_meta( $post->ID, '_cmp_data_inicio', true );
	$data_fim    = get_post_meta( $post->ID, '_cmp_data_fim', true );

	if ( '' === $status ) {
		$status = 'quero';
	}
	?>
	<p>
		<label for="cmp_nota"><strong>Nota (0 a 10)</strong></label><br>
		<input type="number" id="cmp_nota" name="cmp_nota" min="0" max="10" step="0.5"
			value="<?php echo esc_attr( $nota ); ?>" style="width:100%;">
	</p>

	<p>
		<label for="cmp_sentimento"><strong>Sentimento final</strong></label><br>
		<select id="cmp_sentimento" name="cmp_sentimento" style="width:100%;">
			<?php foreach ( cmp_get_sentimentos() as $valor => $rotulo ) : ?>
				<option value="<?php echo esc_attr( $valor ); ?>" <?php selected( $sentimento, $valor ); ?>>
					<?php echo esc_html( $rotulo ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<p>
		<label for="cmp_status"><strong>Status</strong></label><br>
		<select id="cmp_status" name="cmp_status" style="width:100%;">
			<?php foreach ( cmp_get_status() as $valor => $rotulo ) : ?>
				<option value="<?php echo esc_attr( $valor ); ?>" <?php selected( $status, $valor ); ?>>
					<?php echo esc_html( $rotulo ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<p>
		<label for="cmp_data_inicio"><strong>Data de início</strong></label><br>
		<input type="date" id="cmp_data_inicio" name="cmp_data_inicio"
			value="<?php echo esc_attr( $data_inicio ); ?>" style="width:100%;">
	</p>

	<p>
		<label for="cmp_data_fim"><strong>Data de término</strong></label><br>
		<input type="date" id="cmp_data_fim" name="cmp_data_fim"
			value="<?php echo esc_attr( $data_fim ); ?>" style="width:100%;">
	</p>
	<?php
}

function cmp_salvar_meta_box( $post_id ) {
	if ( ! isset( $_POST['cmp_avaliacao_nonce'] ) ||
		! wp_verify_nonce( $_POST['cmp_avaliacao_nonce'], 'cmp_salvar_avaliacao' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['cmp_nota'] ) ) {
		$nota = floatval( $_POST['cmp_nota'] );
		$nota = max( 0, min( 10, $nota ) );
		update_post_meta( $post_id, '_cmp_nota', $nota );
	}

	if ( isset( $_POST['cmp_sentimento'] ) ) {
		$sentimentos_validos = array_keys( cmp_get_sentimentos() );
		$sentimento          = sanitize_text_field( $_POST['cmp_sentimento'] );
		if ( in_array( $sentimento, $sentimentos_validos, true ) ) {
			update_post_meta( $post_id, '_cmp_sentimento', $sentimento );
		}
	}

	if ( isset( $_POST['cmp_status'] ) ) {
		$status_validos = array_keys( cmp_get_status() );
		$status         = sanitize_text_field( $_POST['cmp_status'] );
		if ( in_array( $status, $status_validos, true ) ) {
			update_post_meta( $post_id, '_cmp_status', $status );
		}
	}

	if ( isset( $_POST['cmp_data_inicio'] ) ) {
		update_post_meta( $post_id, '_cmp_data_inicio', sanitize_text_field( $_POST['cmp_data_inicio'] ) );
	}

	if ( isset( $_POST['cmp_data_fim'] ) ) {
		update_post_meta( $post_id, '_cmp_data_fim', sanitize_text_field( $_POST['cmp_data_fim'] ) );
	}
}
add_action( 'save_post_' . CMP_POST_TYPE, 'cmp_salvar_meta_box' );

/**
 * ===========================================================
 * 4. EXIBIÇÃO NO CONTEÚDO (sem precisar de template próprio)
 * ===========================================================
 * Insere a caixa de avaliação automaticamente no início do
 * conteúdo, dentro do template padrão do tema.
 */
function cmp_exibir_avaliacao_no_conteudo( $content ) {
	if ( ! is_singular( CMP_POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id     = get_the_ID();
	$nota        = get_post_meta( $post_id, '_cmp_nota', true );
	$sentimento  = get_post_meta( $post_id, '_cmp_sentimento', true );
	$status      = get_post_meta( $post_id, '_cmp_status', true );
	$data_inicio = get_post_meta( $post_id, '_cmp_data_inicio', true );
	$data_fim    = get_post_meta( $post_id, '_cmp_data_fim', true );

	$sentimentos = cmp_get_sentimentos();
	$status_list = cmp_get_status();

	ob_start();
	?>
	<div class="cmp-avaliacao-box">
		<?php if ( '' !== $nota ) : ?>
			<span class="cmp-nota">⭐ Nota: <strong><?php echo esc_html( $nota ); ?>/10</strong></span>
		<?php endif; ?>

		<?php if ( ! empty( $sentimento ) && isset( $sentimentos[ $sentimento ] ) ) : ?>
			<span class="cmp-sentimento"><?php echo esc_html( $sentimentos[ $sentimento ] ); ?></span>
		<?php endif; ?>

		<?php if ( ! empty( $status ) && isset( $status_list[ $status ] ) ) : ?>
			<span class="cmp-status">Status: <strong><?php echo esc_html( $status_list[ $status ] ); ?></strong></span>
		<?php endif; ?>

		<?php if ( ! empty( $data_inicio ) ) : ?>
			<span class="cmp-data">Início: <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $data_inicio ) ) ); ?></span>
		<?php endif; ?>

		<?php if ( ! empty( $data_fim ) ) : ?>
			<span class="cmp-data">Fim: <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $data_fim ) ) ); ?></span>
		<?php endif; ?>
	</div>
	<?php
	$box = ob_get_clean();

	return $box . $content;
}
add_filter( 'the_content', 'cmp_exibir_avaliacao_no_conteudo' );

/**
 * ===========================================================
 * 5. CSS BÁSICO (funciona em qualquer tema)
 * ===========================================================
 */
function cmp_enqueue_estilos() {
	$css = "
	.cmp-avaliacao-box {
		display: flex;
		flex-wrap: wrap;
		gap: 10px 16px;
		align-items: center;
		background: #f7f7f7;
		border: 1px solid #e0e0e0;
		border-radius: 6px;
		padding: 10px 14px;
		margin-bottom: 20px;
		font-size: 14px;
	}
	.cmp-avaliacao-box span { white-space: nowrap; }

	.cmp-widget-lista { list-style: none; margin: 0; padding: 0; }
	.cmp-widget-item {
		display: flex;
		gap: 10px;
		align-items: center;
		padding: 8px 0;
		border-bottom: 1px solid #eee;
	}
	.cmp-widget-item:last-child { border-bottom: none; }
	.cmp-widget-thumb img {
		width: 50px;
		height: 75px;
		object-fit: cover;
		border-radius: 4px;
		display: block;
		box-shadow: 0 1px 3px rgba(0,0,0,0.15);
	}
	.cmp-widget-info { display: flex; flex-direction: column; }
	.cmp-widget-titulo { font-weight: 600; text-decoration: none; }
	.cmp-widget-meta { font-size: 12px; opacity: 0.75; }
	.cmp-widget-vazio { font-size: 13px; opacity: 0.75; }
	";
	wp_register_style( 'cmp-estilos', false, array(), CMP_VERSION );
	wp_enqueue_style( 'cmp-estilos' );
	wp_add_inline_style( 'cmp-estilos', $css );
}
add_action( 'wp_enqueue_scripts', 'cmp_enqueue_estilos' );

/**
 * ===========================================================
 * 6. WIDGET DE SIDEBAR: "Em andamento agora"
 * ===========================================================
 * Mostra os itens com status "Em andamento" (lendo/assistindo),
 * ordenados pela data de início mais recente primeiro.
 */
class CMP_Widget_Em_Andamento extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'cmp_widget_em_andamento',
			'Catálogo: Em andamento agora',
			array( 'description' => 'Mostra os filmes, livros ou séries que você está consumindo no momento, com base na data de início.' )
		);
	}

	public function form( $instance ) {
		$titulo    = isset( $instance['titulo'] ) ? $instance['titulo'] : 'Lendo e assistindo agora';
		$quantidade = isset( $instance['quantidade'] ) ? absint( $instance['quantidade'] ) : 5;
		$tipo_filtro = isset( $instance['tipo_filtro'] ) ? $instance['tipo_filtro'] : '';

		$termos = get_terms(
			array(
				'taxonomy'   => CMP_TAXONOMY,
				'hide_empty' => false,
			)
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'titulo' ) ); ?>">Título:</label>
			<input class="widefat" type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'titulo' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'titulo' ) ); ?>"
				value="<?php echo esc_attr( $titulo ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'quantidade' ) ); ?>">Número de itens:</label>
			<input class="widefat" type="number" min="1" max="20"
				id="<?php echo esc_attr( $this->get_field_id( 'quantidade' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'quantidade' ) ); ?>"
				value="<?php echo esc_attr( $quantidade ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'tipo_filtro' ) ); ?>">Filtrar por tipo (opcional):</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'tipo_filtro' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'tipo_filtro' ) ); ?>">
				<option value=""<?php selected( $tipo_filtro, '' ); ?>>Todos os tipos</option>
				<?php if ( ! is_wp_error( $termos ) ) : ?>
					<?php foreach ( $termos as $termo ) : ?>
						<option value="<?php echo esc_attr( $termo->slug ); ?>" <?php selected( $tipo_filtro, $termo->slug ); ?>>
							<?php echo esc_html( $termo->name ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance               = array();
		$instance['titulo']     = sanitize_text_field( $new_instance['titulo'] );
		$instance['quantidade'] = absint( $new_instance['quantidade'] );
		$instance['tipo_filtro'] = sanitize_text_field( $new_instance['tipo_filtro'] );
		return $instance;
	}

	public function widget( $args, $instance ) {
		$titulo      = ! empty( $instance['titulo'] ) ? $instance['titulo'] : 'Lendo e assistindo agora';
		$quantidade  = ! empty( $instance['quantidade'] ) ? absint( $instance['quantidade'] ) : 5;
		$tipo_filtro = ! empty( $instance['tipo_filtro'] ) ? $instance['tipo_filtro'] : '';

		$query_args = array(
			'post_type'      => CMP_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $quantidade,
			'meta_key'       => '_cmp_data_inicio',
			'orderby'        => 'meta_value',
			'meta_type'      => 'DATE',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_cmp_status',
					'value' => 'andamento',
				),
			),
		);

		if ( ! empty( $tipo_filtro ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => CMP_TAXONOMY,
					'field'    => 'slug',
					'terms'    => $tipo_filtro,
				),
			);
		}

		$consulta = new WP_Query( $query_args );

		echo wp_kses_post( $args['before_widget'] );
		if ( ! empty( $titulo ) ) {
			echo wp_kses_post( $args['before_title'] . esc_html( $titulo ) . $args['after_title'] );
		}

		if ( $consulta->have_posts() ) {
			echo '<ul class="cmp-widget-lista">';
			while ( $consulta->have_posts() ) {
				$consulta->the_post();
				$post_id     = get_the_ID();
				$data_inicio = get_post_meta( $post_id, '_cmp_data_inicio', true );
				$termos      = get_the_terms( $post_id, CMP_TAXONOMY );
				$tipo_nome   = ( $termos && ! is_wp_error( $termos ) ) ? $termos[0]->name : '';
				?>
				<li class="cmp-widget-item">
					<?php if ( has_post_thumbnail() ) : ?>
						<span class="cmp-widget-thumb">
							<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'cmp_capa_retrato' ); ?></a>
						</span>
					<?php endif; ?>
					<span class="cmp-widget-info">
						<a class="cmp-widget-titulo" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<span class="cmp-widget-meta">
							<?php echo esc_html( $tipo_nome ); ?>
							<?php if ( ! empty( $data_inicio ) ) : ?>
								· desde <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $data_inicio ) ) ); ?>
							<?php endif; ?>
						</span>
					</span>
				</li>
				<?php
			}
			echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p class="cmp-widget-vazio">Nada em andamento no momento.</p>';
		}

		echo wp_kses_post( $args['after_widget'] );
	}
}

function cmp_registrar_widget() {
	register_widget( 'CMP_Widget_Em_Andamento' );
}
add_action( 'widgets_init', 'cmp_registrar_widget' );