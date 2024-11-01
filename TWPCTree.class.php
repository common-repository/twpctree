<?php
/*
Plugin Name: TWPC Tree
Plugin URI: http://thewordpresscart.com
Description: Widget category tree
Author: TheWordPressCart team
Version: 3.0.1
Author URI: http://thewordpresscart.com
*/

/**
 * This file is part of TWPCTree.
 * 
 * TWPCTree is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TWPCTree is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TWPCTree.  If not, see <http://www.gnu.org/licenses/>.
 */

$REDFRUITS_PATH = dirname(dirname(__FILE__)).'/redfruits/';

require_once($REDFRUITS_PATH.'gui/ADCoreGui.php');
require_once($REDFRUITS_PATH.'gui/wp-gui/ADWPCoreGui.php');

class TWPCTree
{
	protected $categoriesOrder;

	function __construct()
	{
		add_action('widgets_init', array($this, 'registerWidgets'));
	}

	function registerWidgets()
	{
		register_widget('WidgetTWPCTree');
	}
}

class WidgetTWPCTree extends WP_Widget
{
	function WidgetTWPCTree()
	{
		$widget = array(
			'classname' => 'twpc_tree',
			'description' => __('Use this widget to add a product\'s category tree', 'twpc_tree'),
		);
		$control = array(
			'width' => 800,
			//'height' => 350,
			'id_base' => 'twpc_tree-widget',
		);
		$this->WP_Widget('twpc_tree-widget', 'TWPC Tree', $widget, $control);
	}

	function widget($args, $instance)
	{
		extract($args);
		$title = apply_filters('widget_title', $instance['twpc_tree_title'] );
		$taxonomy = $instance['twpc_tree_taxonomy'];
		$seeNumberProducts = $instance['twpc_tree_see_number_products'] == 'Y';
		$hideEmptyCategories = $instance['twpc_tree_hide_empty_categories'] == 'Y';
		$excludedCategories = $instance['twpc_tree_excluded_categories'];
		$includedCategories = $instance['twpc_tree_included_categories'];
		$categoriesOrder = $instance['twpc_tree_categories_order'];
		echo $before_widget;
		if ($title)	echo $before_title, $title, $after_title;
		$args = array(
			//'show_option_all'    => ,
			//'orderby'            => 'name',
			//'order'              => 'ASC',
			'show_last_update'   => 0,
			'style'              => 'list',
			'show_count'         => ($seeNumberProducts)?1:0,
			'hide_empty'         => ($hideEmptyCategories)?1:0,
			'use_desc_for_title' => 1,
			'child_of'           => 0,
			//'feed'               => ,
			//'feed_type'          => ,
			//'feed_image'         => ,
			//'exclude'            => ,
			//'exclude_tree'       => ,
			//'include'            => ,
			'current_category'   => 0,
			'hierarchical'       => true,
			'title_li'           => '', //$options['txt_title_li'],
			'number'             => NULL,
			'echo'               => 0,
			'depth'              => 0,
			'taxonomy'			 => $taxonomy,
		);
		if (is_array($excludedCategories)) $args['exclude'] = implode(",", $excludedCategories);
		if (is_array($includedCategories)) $args['include'] = implode(",", $includedCategories);

		if (strlen($categoriesOrder) > 0)
		{
			$this->categoriesOrder = explode('#', $categoriesOrder);
			add_filter('get_terms', array($this, 'order_categories'));
		}
		echo '<ul>'.wp_list_categories($args).'</ul>';
		if (strlen($categoriesOrder) > 0) remove_filter('get_terms', array($this, 'order_categories'));
		echo $after_widget;
	}

	//for order categories list
	function order_categories($terms)
	{
		usort($terms, array($this, 'compare'));
		return $terms;
	}

	//for order categories list
	function compare($a, $b)
	{
		if ($a == $b) return 0;
		foreach($this->categoriesOrder as $id)
			if ($id == $a->term_id) return -1;
			elseif ($id == $b->term_id) return 1;
		return 0;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance['twpc_tree_title'] = strip_tags($new_instance['twpc_tree_title']);
		$instance['twpc_tree_taxonomy'] = $new_instance['twpc_tree_taxonomy'];
		$instance['twpc_tree_see_number_products'] = $new_instance['twpc_tree_see_number_products'];
		$instance['twpc_tree_hide_empty_categories'] = $new_instance['twpc_tree_hide_empty_categories'];
		$instance['twpc_tree_excluded_categories'] = $new_instance['twpc_tree_excluded_categories'];
		$instance['twpc_tree_included_categories'] = $new_instance['twpc_tree_included_categories'];
		$instance['twpc_tree_categories_order'] = $new_instance['twpc_tree_categories_order'];
		return $instance;
	}

	function form($instance)
	{
		$defaults = array(
			'twpc_tree_title'				=> '',
			'twpc_tree_taxonomy'					=> 'Post',
			'twpc_tree_see_number_products'	=> 'Y',
			'twpc_tree_hide_empty_categories'	=> 'Y',
			'twpc_tree_excluded_categories'	=> array(-1, -1),
			'twpc_tree_included_categories'	=> array(-1, -1),
			'twpc_tree_categories_order'		=> array(),
		);
		$instance = wp_parse_args((array)$instance, $defaults);
		$form = new ADWPMetaBoxForm();
		$form->add(new ADLabeledField(__('Title:', 'twpc_tree'), new ADTextField($this->get_field_name('twpc_tree_title'), $instance['twpc_tree_title'], 20, 50)));

		$name = $this->get_field_name('twpc_tree_taxonomy');
		$taxonomySelected = new ADSelectField($name);
		$taxonomy = $instance['twpc_tree_taxonomy'];
		$taxonomySelected->setListModel(new TWPCTreeTaxonomiesListModel())
						 ->setValue($taxonomy);
  		$form->add(new ADLabeledField(__('Taxonomy:', 'twpc_tree'), $taxonomySelected));

		$form->add(new ADLabeledField(__('See children number:', 'twpc_tree'), new ADCheckBox($this->get_field_name('twpc_tree_see_number_products'), 'Y', $instance['twpc_tree_see_number_products'])));
		$form->add(new ADLabeledField(__('Hide empty categories:', 'twpc_tree'), new ADCheckBox($this->get_field_name('twpc_tree_hide_empty_categories'), 'Y', $instance['twpc_tree_hide_empty_categories'])));
		$args = array(
			'taxonomy'		=> $taxonomy,
			'hide_empty'	=> false,
		);
		if ($instance['twpc_tree_excluded_categories']) $excludedCategories = implode(',', $instance['twpc_tree_excluded_categories']);
		else $excludedCategories = '';

		$form->add(new ADJavaScript('
			function twpc_tree_select_up_item(select)
			{
				var currernt;
				var reverse;
				try
				{
					if(select.options[select.options.selectedIndex].index > 0)
					{
						current = select.options[select.options.selectedIndex].text;
						reverse = select.options[select.options[select.options.selectedIndex].index-1].text;
						select.options[select.options.selectedIndex].text = reverse;
						select.options[select.options[select.options.selectedIndex].index-1].text = current;
			
						current = select.options[select.options.selectedIndex].value;
						reverse = select.options[select.options[select.options.selectedIndex].index-1].value;
						select.options[select.options.selectedIndex].value = reverse;
						select.options[select.options[select.options.selectedIndex].index-1].value = current;
						self.focus();
						select.options.selectedIndex--;
					}
				}
				catch(ex) {}
			}

			function twpc_tree_select_down_item(select)
			{
				var currernt;
				var next;
				try
				{
					if (select.options[select.options.selectedIndex].index != select.length - 1)
					{
						current = select.options[select.options.selectedIndex].text;
						next = select.options[select.options[select.options.selectedIndex].index+1].text;
						select.options[select.options.selectedIndex].text =  next;
						select.options[select.options[select.options.selectedIndex].index+1].text = current;

						current = select.options[select.options.selectedIndex].value;
						next = select.options[select.options[select.options.selectedIndex].index+1].value;
						select.options[select.options.selectedIndex].value =  next;
						select.options[select.options[select.options.selectedIndex].index+1].value = current;

						self.focus();
						select.options.selectedIndex++;
					}
				}
				catch(ex) {}
			}
			
			function twpc_tree_load_select_values_to_textbox(sel_from, txt_to)
			{
					txt_to.value = "";
					var lenValues = sel_from.length;
					var i;
					for(i = 0; i < lenValues; i++)
						txt_to.value += sel_from.options[i].value + "#";
					if (txt_to.value.length > 0)
						txt_to.value = txt_to.value.substr(0, txt_to.value.length - 1);
			}'));
		$name = $this->get_field_name('twpc_tree_excluded_categories').'[]';
		$excludedSelected = new ADSelectField($name, '', true, 6);
		$excludedSelected->setStyle('height: auto;')
						 ->setListModel(new TWPCTreeCategoriesListModel('id', true, $taxonomy))
						 ->setValue($excludedCategories);
  		$lbl = $form->add(new ADLabeledField(__('Categories to exclude:', 'twpc_tree'), $excludedSelected));

		if ($instance['twpc_tree_included_categories']) $includedCategories = implode(',', $instance['twpc_tree_included_categories']);
		else $includedCategories = '';
		$name = $this->get_field_name('twpc_tree_included_categories').'[]';		
		$includedSelected = new ADSelectField($name, '', true, 6);
		$includedSelected->setStyle('height: auto;')
						 ->setListModel(new TWPCTreeCategoriesListModel('id', true, $taxonomy))
						 ->setValue($includedCategories);
  		$lbl->add(new ADLabeledField(__('Categories to include:', 'twpc_tree'), $includedSelected))
	  		->setStyle('vertical-align: top;');
		$allCategories = new ADSelectField('twpc_tree_all_categories', '', false, 6);
		$allCategories->setStyle('height: auto; float: left;')
						 ->setListModel(new TWPCTreeCategoriesListModel('id', false, $taxonomy, explode('#', $instance['twpc_tree_categories_order'])));

		$form->add(new ADHiddenField('twpc_tree_categories_order', $instance['twpc_tree_categories_order'], 20, 50))
			->setName($this->get_field_name('twpc_tree_categories_order'));
		$lbl = $form->add(new ADLabeledField(__('Order categories:', 'twpc_tree'), $allCategories));
		$ul = $lbl->add(new ADUList());
		$btnUp = new ADButton(__('up', 'twpc_tree'));
		$btnUp->setOnClick('twpc_tree_select_up_item(twpc_tree_all_categories);twpc_tree_load_select_values_to_textbox(twpctree__all_categories, twpc_tree_categories_order);');
		$ul->add($btnUp);
		$btnDown = new ADButton(__('down', 'twpc_tree'));
		$btnDown->setOnClick('twpc_tree_select_down_item(twpc_tree_all_categories);twpc_tree_load_select_values_to_textbox(twpc_tree_all_categories, twpc_tree_categories_order);');
		$ul->add($btnDown);

		echo $form->render(ADMIN_RENDER);
	}
}

class TWPCTreeTaxonomiesListModel extends ADListModel
{
	function __construct()
	{
		$args = array(
			'hierarchical'	=> true,
		);
		$taxonomies = get_taxonomies($args);
		foreach($taxonomies as $taxonomy)
			$this->addValue($taxonomy, $taxonomy);
	}
}

/**
 * Class required to create the categories select control
 */
class TWPCTreeCategoriesListModel extends ADListModel
{
	protected $order;
	/**
	 * Creates categories list model
	 *
	 * @param $id can be 'id', to returns the id; or 'slug' to return the slug
 	 * @param $includeNone if true incudes a first option called 'no selected'
	 * @param $taxonomy Post by default
	 * @param $order
	 */
	function __construct($id = 'id', $includeNone = false, $taxonomy = 'Post', $order = '')
	{
		$args = array();
		if ($taxonomy != 'Post') $args['taxonomy'] = $taxonomy;
		$args['hide_empty'] = false;
 		if ($includeNone) $this->addValue('0', __('no one selected', 'twpc_tree'));
		$categories = get_categories($args);
		if (is_array($order))
		{
			$this->order = $order;
			usort($categories, array($this, 'compare'));
		}
		foreach ($categories as $cat)
			if ($id == 'slug')
				$this->addValue($cat->slug, $cat->cat_name);
			else //if ($id == 'id')
				$this->addValue($cat->term_id, $cat->cat_name);
	}

	function compare($a, $b)
	{
		if ($a == $b) return 0;
		foreach($this->order as $id)
			if ($id == $a->term_id) return -1;
			elseif ($id == $b->term_id) return 1;
		return 0;
	}
}

new TWPCTree();
?>
