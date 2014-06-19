<?php
require_once(dirname(__FILE__) . '/class.db.php');
require_once(dirname(__FILE__) . '/class.tree.php');

if(isset($_GET['operation'])) {
	$fs = new tree(db::get('mysqli://root@127.0.0.1/jstree'), array('structure_table' => 'tree_struct', 'data_table' => 'tree_data', 'data' => array('nm')));
	try {
		$rslt = null;
		switch($_GET['operation']) {
			case 'get_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? (int)$_GET['id'] : 0;
				$temp = $fs->get_children($node);
				$rslt = array();
				foreach($temp as $v) {
					$rslt[] = array('id' => $v['id'], 'text' => $v['nm'], 'children' => ($v['rgt'] - $v['lft'] > 1));
				}
				break;
			case "get_content":
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : 0;
				$node = explode(':', $node);
				if(count($node) > 1) {
					$rslt = array('content' => 'Multiple selected');
				}
				else {
					$temp = $fs->get_node((int)$node[0], array('with_path' => true));
					$rslt = array('content' => 'Selected: /' . implode('/',array_map(function ($v) { return $v['nm']; }, $temp['path'])). '/'.$temp['nm']);
				}
				break;
			case 'create_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? (int)$_GET['id'] : 0;
				$temp = $fs->mk($node, isset($_GET['position']) ? (int)$_GET['position'] : 0, array('nm' => isset($_GET['text']) ? $_GET['text'] : 'New node'));
				$rslt = array('id' => $temp);
				break;
			case 'rename_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? (int)$_GET['id'] : 0;
				$rslt = $fs->rn($node, array('nm' => isset($_GET['text']) ? $_GET['text'] : 'Renamed node'));
				break;
			case 'delete_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? (int)$_GET['id'] : 0;
				$rslt = $fs->rm($node);
				break;
			case 'move_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? (int)$_GET['id'] : 0;
				$parn = isset($_GET['parent']) && $_GET['parent'] !== '#' ? (int)$_GET['parent'] : 0;
				$rslt = $fs->mv($node, $parn, isset($_GET['position']) ? (int)$_GET['position'] : 0);
				break;
			case 'copy_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? (int)$_GET['id'] : 0;
				$parn = isset($_GET['parent']) && $_GET['parent'] !== '#' ? (int)$_GET['parent'] : 0;
				$rslt = $fs->cp($node, $parn, isset($_GET['position']) ? (int)$_GET['position'] : 0);
				break;
			case 'create_product':
				$rslt = $fs->new_products($_GET['id'], $_GET['num']);
				break;
			default:
				throw new Exception('Unsupported operation: ' . $_GET['operation']);
				break;
		}
		header('Content-Type: application/json; charset=utf8');
		echo json_encode($rslt);
	}
	catch (Exception $e) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 500 Server Error');
		header('Status:  500 Server Error');
		echo $e->getMessage();
	}
	die();
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title>Title</title>
		<meta name="viewport" content="width=device-width" />
		<link rel="stylesheet" href="css/style.min.css" />
		<style>
		html, body { background:#ebebeb; font-size:10px; font-family:Verdana; margin:0; padding:0; }
		#container { min-width:320px; margin:0px auto 0 auto; background:white; border-radius:0px; padding:0px; overflow:hidden; }
		#tree { float:left; min-width:319px; border-right:1px solid silver; overflow:auto; padding:0px 0; }
		#data { margin-left:320px; }
		#data textarea { margin:0; padding:0; height:100%; width:100%; border:0; background:white; display:block; line-height:18px; }
		#data, #code { font: normal normal normal 12px/18px 'Consolas', monospace !important; }
		</style>
	</head>
	<body>
		<div id="container" role="main">
			<div id="tree"></div>
			<div id="inp" style="display:none">
					<input style="margin-left: 33%;margin-top: 13%;" id="num" placeholder="Enter the number of product" type="text" />
					<button onclick="genprod()">Submit</button>
			</div>
			<div id="data">
				<div class="content code" style="display:none;"><textarea id="code" readonly="readonly"></textarea></div>
				<div class="content folder" style="display:none;"></div>
				<div class="content image" style="display:none; position:relative;"><img src="" alt="" style="display:block; position:absolute; left:50%; top:50%; padding:0; max-height:90%; max-width:90%;" /></div>
				<div class="content default" style="text-align:center;">Select a node from the tree.</div>
			</div>
		</div>

		<script src="js/jquery.js"></script>
		<script src="js/jstree.min.js"></script>
		<script>
		var ext_node = 0;
		var tree;
		$(function () {
			$(window).resize(function () {
				var h = Math.max($(window).height() - 0, 420);
				$('#container, #data, #tree, #data .content').height(h).filter('.default').css('lineHeight', h + 'px');
			}).resize();

			$('#tree')
				.jstree({
					'core' : {
						'data' : {
							'url' : '?operation=get_node',
							'data' : function (node) {
								return { 'id' : node.id };
							}
						},
						'check_callback' : true,
						'themes' : {
							'responsive' : false
						}
					},
					'plugins' : ['state','dnd','contextmenu','wholerow'],
					"contextmenu": {
				        "items": function (node) {
				        	tree = $("#tree").jstree(true);
				        	var parent = node.parent;
				        	if(parent == 1)
					        	return {
					                "Create": {
					                    "label": "Generate",
					                    "action": function (obj) {
					                    	$("#inp").fadeIn();
					                    	ext_node = node;
					                  		//tree.create_node(node, "hell");
					                    }
					                },
					                "Rename": {
					                	
					                    "label": "Rename Factory",
					                    "action": function (obj) {
					                    	document.getElementById('num').value = '';
											$("#inp").fadeOut();
					                        tree.edit(node);
					                    }
					                },
					                "Delete": {
					                	
					                    "label": "Delete Factory",
					                    "action": function (obj) {
					                    	document.getElementById('num').value = '';
											$("#inp").fadeOut();
					                        tree.delete_node(node);
					                    }
					                }
					            };
					        else if(parent != "#"){
					        	return{
					        		"Delete": {
					        			
					                    "label": "Delete Product",
					                    "action": function (obj) {
					                    	document.getElementById('num').value = '';
											$("#inp").fadeOut();
					                        tree.delete_node(node);
					                    }
					                }
					        	};
					        }
					        else{
					        	return {
					                "Create": {
					                    "label": "Create Factory",
					                    "action": function (obj) {
					                    	document.getElementById('num').value = '';
											$("#inp").fadeOut();
					                    	tree.create_node(node, "Factory #: (1-1000)");
					                    }
					                }
					            };
					        }
				        }
				    }
				})
				.on('delete_node.jstree', function (e, data) {
					$.get('?operation=delete_node', { 'id' : data.node.id })
						.fail(function () {
							data.instance.refresh();
						});
				})
				.on('create_node.jstree', function (e, data) {
					$.get('?operation=create_node', { 'id' : data.node.parent, 'position' : data.position, 'text' : data.node.text })
						.done(function (d) {
							data.instance.set_id(data.node, d.id);
						})
						.fail(function () {
							data.instance.refresh();
						});
				})
				.on('rename_node.jstree', function (e, data) {
					$.get('?operation=rename_node', { 'id' : data.node.id, 'text' : data.text })
						.fail(function () {
							data.instance.refresh();
						});
				})
				.on('move_node.jstree', function (e, data) {
					$.get('?operation=move_node', { 'id' : data.node.id, 'parent' : data.parent, 'position' : data.position })
						.fail(function () {
							data.instance.refresh();
						});
				})
				.on('copy_node.jstree', function (e, data) {
					$.get('?operation=copy_node', { 'id' : data.original.id, 'parent' : data.parent, 'position' : data.position })
						.always(function () {
							data.instance.refresh();
						});
				})
				.on('changed.jstree', function (e, data) {
					document.getElementById('num').value = '';
					$("#inp").fadeOut();
					if(data && data.selected && data.selected.length) {
						$.get('?operation=get_content&id=' + data.selected.join(':'), function (d) {
							$('#data .default').html(d.content).show();
						});
					}
					else {
						$('#data .content').hide();
						$('#data .default').html('Select a product or factory from the tree.').show();
					}
				});
		});


		function genprod(){
			var num = document.getElementById('num').value;
			var parent_id = ext_node.id;
			$.get('?operation=create_product', { 'id' : parent_id, 'num' : num })
				.done(function (d) {
					d = JSON.parse(d);
					for(var i=0;i<d.length;i++)
						tree.create_node(ext_node, ""+d[i]);
					document.getElementById('num').value = '';
					$("#inp").fadeOut();
					setTimeout(function(){
						$("#tree").jstree("refresh");
					}, 1000);
				})
		}

		</script>
	</body>
</html>