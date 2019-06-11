<!--
 * Database Topology Mapper is a tool that pairs master & slave servers into a beautiful map using D3.js
 * that is easily digestible for DBAs and anyone else in your organization.
 *
 * @author     Charles Thompson <01charles.t@gmail.com>
 * @copyright  2019 Charles Thompson
 * @license    http://opensource.org/licenses/MIT
 * @link       https://github.com/vuther/db-topology-mapper
 * @version    1.0
-->
<html lang="en">
<head>
	<title>Database Topology Mapper</title>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<script src="https://d3js.org/d3.v3.min.js"></script>
	<script src="https://code.jquery.com/jquery-3.4.1.js" integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>
	<script src="chosen1.7/chosen.jquery.js"></script>

	<link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="chosen1.7/chosen_topology.css" />
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body>
	<div class="page-content-toggle" id="page-content-wrapper">
		<div class="container-fluid">
			<div id="main" style="display: none;">
				<center><select id="servers" class="chosen" data-placeholder="Select a server to search"><option value=""></option></select></center>

				<!-- <a href=""><img src="" class="header" /></a> -->
				<img id="blowup" onclick="blowupNodes()" />
				<p class="last_updated"></p>
				<div id="tree-container"></div>
			</div>
		</div>
	</div>

  <script>
    $.ajax({
    	url: "create_topology.php",
        dataType: "json",
        success: function(data) {
        	$("#main").fadeIn(1500);

			treeData     = data[0];
			servers      = data[1];
			server_names = data[2];
			environments = data[3];
			last_updated = data[4];

			$(document).triggerHandler('READYTOGO', [data]);
        }
    });

	$(document).on('READYTOGO', function(event, data) {
		$('.last_updated').text(last_updated); // Update last updated footer

		//////////////////////////
		// CHOSEN DROPDOWN CODE //
		//////////////////////////
	    var openBranches = new Array();
        function getChains(branch) {
	        var opened = (("children" in branch) && branch['children']) ? true : false;
	        if (opened) {
	            for (var n=0;n<branch['children'].length;n++) {
	                getChains(branch['children'][n]);
	        	}
	        } else {
				openBranches.push(branch['name']);
	        }
	    }

	    // Populate chosen dropdown with servers
	    var html;
	    for (var server in server_names) {
			html += "<option value='" + server_names[server] + "'>" + server_names[server] + "</option>";
	    }
	    $('#servers').append(html);

	    // Chosen dropdown functions
	    $('.chosen').each(function(i, elm) {
	      $(elm).chosen({
	          "width": "255px",
	          "allow_single_deselect": true,
	          search_contains: true
	      });
	    }).change(function(evt, params) {
	      var server_id = params ? params['selected'] : $(evt.target).val();
	      if (!params && !server_id) {  // This condition will be met when the x is clicked to clear the current selection.
	        root.children.forEach(function(child) {
	            collapse(child);
	        });
	        clearAll(root);
	        update(root, true);
	        alignNode(root, 'left');
	      } else {
	      	clearAll(root);

	        var server  = servers[server_id];
	        var masters = new Array();
	        var done;

	        // Create masters path to selected server
	        masters.push(server_id);
	        masters.push(server['master']);

	        while (!done) {
	          server = servers[masters[masters.length - 1]];

	          if (!server || !server['master'] || server['master'] == masters[masters.length - 1]) {
	            done = true;
	          } else {
	            masters.push(server['master']);
	          }
	        }

	        masters.reverse();

	        // Interpret the chain of masters as array keys
	        var keys = new Array();
	        var currentBranch = [treeData];
	        for (var i=0;i<masters.length;i++) {
	          var found = false;
	          for (var n=0;n<currentBranch.length;n++) {
	            if (masters[i] == currentBranch[n]['name']) {
	              keys.push(n);

	              var children = (("children" in currentBranch[n]) && currentBranch[n]['children']) ? 'children' : '_children';
	              currentBranch = currentBranch[n][children];
	              break;
	            }
	          }
	        }

	        // Get all currently open branches
	        openBranches = new Array();
	        getChains(treeData);
	        var chains = new Array();
	        for (var i=0;i<openBranches.length;i++) {
	          var chain = new Array();
	          var server = servers[openBranches[i]];
	          while (server['master'] && server['master'] != server['name']) {
	            chain.push(server['master']);
	            server = servers[server['master']];
	          }
	          chain.reverse();
	          chain.push(openBranches[i]);

	          // Interpret each currently open branch as array keys
	          var openKeys = new Array();
	          var currentBranch = [treeData];
	          for (var x=0;x<chain.length;x++) {
	            var found = false;
	            for (var n=0;n<currentBranch.length;n++) {
	              if (chain[x] == currentBranch[n]['name']) {
	                openKeys.push(n);
	                var children = (("children" in currentBranch[n]) && currentBranch[n]['children']) ? 'children' : '_children';
	                currentBranch = currentBranch[n][children];
	                break;
	              }
	            }
	          }
	          chains.push(openKeys);
	        }

	        // For each branch, determine which has the most in common with the target chain
	        var intersect = new Array();
	        var idSet = 0;
	        for (var i=0;i<chains.length;i++) {
	          var currentChain = new Array();
	          for (var n=0;n<chains[i].length;n++) {
	            if (keys.length-1 >= n && keys[n] == chains[i][n]) {
	              currentChain.push(keys[n]);
	            } else {
	              break;
	            }
	          }
	          if (currentChain.length > intersect.length) {
	            intersect = currentChain;
	            idSet = i;
	          }
	        }

	        // For each branch not currently part of the target change, collapse it
	        var oldBranches = new Array();
	        for (var i=0;i<chains.length;i++) {
	          if (i == idSet) {
	            continue;
	          }
	          var currentBranch = {"children": [treeData]};
	          for (var n=0;n<chains[i].length;n++) {
	            currentBranch = currentBranch['children'][chains[i][n]];
	            if (intersect.length-1 < n || chains[i][n] != intersect[n]) {
	              break;
	            }
	          }
	          if ("children" in currentBranch && currentBranch["children"]) {
	            collapse(currentBranch);
	            update(currentBranch);
	            alignNode(currentBranch, 'center');
	          }
	        }

	        collapse(treeData);

	        var target = {"children": [treeData]};
	        var sleepTime = 500;

	        for (var i=0;i<keys.length;i++) {
	          var children = (("children" in target) && target['children']) ? 'children' : '_children';

	          if (intersect[i] === undefined) {
	            sleepTime += 550;
	          }

	          target = target[children][keys[i]];
	          setTimeout(function(target) {
	            toggle(target);
	            target.class = 'found';
	            update(target);
	            alignNode(target, 'center');
	          }, sleepTime, target);
	        }
	      }
	    });

		///////////////////
		// MAIN MAP CODE //
		///////////////////

	    // Calculate total nodes, max label length
	    var totalNodes = 0;
	    var maxLabelLength = 0;
	    // panning variables
	    var panSpeed = 600;
	    var panBoundary = 20; // Within 20px from edges will pan when dragging.
	    // Misc. variables
	    var i = 0;
	    var duration = 600;
	    var root;

	    // size of the diagram
	    var viewerWidth  = $(document).width();
	    var viewerHeight = $(document).height();

	    // When Window is resized, resize the tree also
	    window.onresize = function() {
	      viewerWidth   = $(document).width();
	      viewerHeight  = $(document).height();
	    }

	    var tree = d3.layout.tree()
	        .size([viewerHeight, viewerWidth])
	        .nodeSize([47, 0]);

	    // define a d3 diagonal projection for use by the node paths later on.
	    var diagonal = d3.svg.diagonal()
	        .projection(function(d) {
	            return [d.y, d.x];
	        });

	    // sort the tree according to the node names
	    function sortTree() {
	        tree.sort(function(a, b) {
	            return b.name.toLowerCase() < a.name.toLowerCase() ? 1 : -1;
	        });
	    }
	    // Sort the tree initially incase the JSON isn't in a sorted order.
	    sortTree();

	    function pan(domNode, direction) {
	        var speed = panSpeed;
	        if (panTimer) {
	            clearTimeout(panTimer);
	            translateCoords = d3.transform(svgGroup.attr("transform"));
	            if (direction == 'left' || direction == 'right') {
	                translateX = direction == 'left' ? translateCoords.translate[0] + speed : translateCoords.translate[0] - speed;
	                translateY = translateCoords.translate[1];
	            } else if (direction == 'up' || direction == 'down') {
	                translateX = translateCoords.translate[0];
	                translateY = direction == 'up' ? translateCoords.translate[1] + speed : translateCoords.translate[1] - speed;
	            }
	            scaleX = translateCoords.scale[0];
	            scaleY = translateCoords.scale[1];
	            scale = zoomListener.scale();
	            svgGroup.transition().attr("transform", "translate(" + translateX + "," + translateY + ")scale(" + scale + ")");
	            d3.select(domNode).select('g.node').attr("transform", "translate(" + translateX + "," + translateY + ")");
	            zoomListener.scale(zoomListener.scale());
	            zoomListener.translate([translateX, translateY]);
	            panTimer = setTimeout(function() {
	                pan(domNode, speed, direction);
	            }, 50);
	        }
	    }

	    // Define the zoom function for the zoomable tree
	    function zoom() {
	        svgGroup.attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")")
	        zoomListener.scaleExtent([zoomListener.scale()*0.9, zoomListener.scale()*1.1]);
	    }

	    // define the zoomListener which calls the zoom function on the "zoom" event constrained within the scaleExtents
	    var zoomListener = d3.behavior.zoom().on("zoom", zoom);

	    // define the baseSvg, attaching a class for styling and the zoomListener
	    var baseSvg = d3.select("#tree-container").append("svg")
	        .attr("class", "overlay")
	        .call(zoomListener).on("dblclick.zoom", null);

	    // Align node either left, center, or default
	    function alignNode(source, type) {
	      scale = zoomListener.scale();
	      x = -source.y0;
	      y = -source.x0;

	      if (type == 'left') {
	        x = (x * scale) + 500;
	      } else {
	        x = x * scale + viewerWidth / 2;
	      }
	      if (type == 'center') {
	        y = y * scale + viewerHeight / 2.3;
	      } else {
	        y = y * scale + viewerHeight / 2.2;
	      }

	      d3.select('g').transition()
	        .duration(duration)
	        .attr("transform", "translate(" + x + "," + y + ")scale(" + scale + ")");
	      zoomListener.scale(scale);
	      zoomListener.translate([x, y]);
	    }

	    function toggle(d) {
	      if (d.children) {
	        d._children = d.children;
	        d.children = null;
	      } else if (d._children) {
	        d.children = d._children;
	        d._children = null;
	      }
	      return d;
	    }
	    function collapse(d) {
	      if (d.children) {
	        d._children = d.children;
	        d._children.forEach(collapse);
	        d.children = null;
	      }
	    }

	    function highlightLine(d) {
	    	if (d.parent) {
    			d.parent.class = "found";
	    		if (d.parent.parent) {
	    			d.parent.parent.class = "found";
	    			if (d.parent.parent.parent) {
	    				d.parent.parent.parent.class = "found";
	    			}
	    		}
	    	}

	    	if (d.children) {
	    		d.class = "found";
	    		d.children.forEach(highlightLine);
	    	} else if (d._children) {
	    		d.class = "found";
		    	d._children.forEach(highlightLine);
	    	} else {
	    		d.class = "found";
	    	}
	    }

	    // Toggle children on click.
	    function click(d) {
	        // if (d3.event.defaultPrevented) return;

	        clearAll(root);
	        d = toggle(d);
	        highlightLine(d);
	        update(d);

	        alignNode(d, "center");

	        $("#servers").val(d.name).trigger("chosen:updated");
	    }

		function clearAll(d) {
		    d.class = "";
		    if (d.children)
		        d.children.forEach(clearAll);
		    else if (d._children)
		        d._children.forEach(clearAll);
		}

	    function update(source, omitHash) {
	    	if (hash != 'blowup') {
	    		window.location.hash = omitHash ? "" : source.name;
	    	}

	        // Compute the new tree layout.
	        var nodes = tree.nodes(root).reverse(),
	            links = tree.links(nodes);

	        // Set widths between levels based on maxLabelLength.
	        nodes.forEach(function(d) {
	            d.y = (d.depth * 400);
	        });

	        // Update the nodes
	        node = svgGroup.selectAll("g.node")
	            .data(nodes, function(d) {
	                return d.id || (d.id = ++i);
	            });

	        // Enter any new nodes at the parent's previous position.
	        var nodeEnter = node.enter().append("g")
	            .attr("class", "node")
	            .attr("opacity", 0)
	            .attr("transform", function(d) {
	                return "translate(" + source.y0 + "," + source.x0 + ")";
	            })
	            .on('click', click);

	        // Create rounded rectangle for server container
	        nodeEnter.append("rect")
	            .attr("r", 0)
	            .attr("x", -2)
	            .attr("y", -21)
	            .attr("rx", 5)
	            .attr("ry", 5)
	            .style("stroke-width", 2)
	            .style("fill", function(d) {
	            	if (d.name == 'Exact environment name') {
	                    return '#d8d844';
					} else if (d.name.includes('Part of environment name')) {
						return '#f9baff';
					} else if (d.name.includes('Part of environment name')) {
						return '#cceeff';
	            	} else if (environments.indexOf(d.name) > -1) {
	                    return '#d4d4d4';
	                } else {
	                    return 'white';
	                }
	            })
	            .style("stroke", function(d) {
					if (d.name == 'Exact environment name') {
	                    return '#5d5d0b';
					} else if (d.name.includes('Part of environment name')) {
   						return '#9d06aa';
					} else if (d.name.includes('Part of environment name')) {
   						return '#005580';
					} else if (environments.indexOf(d.name) > -1) {
	                    return '#3c3c3c';
	                } else {
	                    return '#008000';
	                }
	            })
	            .attr("width", 233)
	            .attr("height", 40)
	            .attr("opacity", function(d) {
	                if (d.name == 'Database Servers') {
	                    return 0;
	                } else {
	                	return .60;
	                }
	            });

	        nodeEnter.append("svg:foreignObject")
	            .attr("x", -9)
	            .attr("y", function(d) {
	            	if (d.name == 'Database Servers') {
	            		return -56;
	            	} else {
	            		return -27;
	            	}
	            })
	            .attr("dy", ".35em")
	            .attr("width", 242)
	            .attr("height", function(d) {
	            	if (d.name == 'Database Servers') {
	            		return 110;
	            	} else {
	            		return 50;
	            	}
	            })
	            .attr("text-anchor", function(d) { return d.children || d._children ? "end" : "start"; })
	            .append("xhtml:body")
		            .style('height', function(d) {
		            	if (d.name == 'Database Servers') {
		            		return 110;
		            	} else {
		            		return 50;
		            	}
	            	})
		            .style('width', '200')
	            .html(function(d) {
	              return d.content;
	            });

	        // Add arrow over image beside server
	        nodeEnter.append("image")
	          .attr("id", "expand")
	          .attr("xlink:href", 'images/arrow.png')
	          .attr("x", function(d) {
	          	if (d.name == 'Database Servers') {
	                return 75;
	            } else {
	            	return 235;
	            }
	          })
	          .attr("y", function(d) {
	          	if (d.name == 'Database Servers') {
	                return -5;
	            } else {
	            	return -9;
	            }
	          })
	          .attr("width", 17)
	          .attr("height", 17)
	          .attr("opacity", 0);

	        // Fade arrow over image
	        node.select("#expand").transition()
	            .duration(600)
	            .style("opacity", function(d) {
	                return d._children ? 1 : 0;
	            });

	        // Transition nodes to their new position
	        var nodeUpdate = node.transition()
	            .duration(duration)
	            .attr("opacity", 1)
	            .attr("transform", function(d) {
	                return "translate(" + d.y + "," + d.x + ")";
	            });

	        // Transition exiting nodes to the parent's new position.
	        var nodeExit = node.exit().transition()
	            .duration(duration)
	            .attr("opacity", 0)
	            .attr("transform", function(d) {
	                return "translate(" + source.y + "," + source.x + ")";
	            })
	            .remove();

	        // Update the links
	        var link = svgGroup.selectAll("path.link")
	            .data(links, function(d) {
	                return d.target.id;
	            });

	        // Enter any new links at the parent's previous position.
	        link.enter().insert("path", "g")
	            .attr("class", "link")
	            .attr("d", function(d) {
	                var o = {
	                    x: source.x0,
	                    y: source.y0
	                };
	                return diagonal({
	                    source: o,
	                    target: o
	                });
	            });

	        // Transition links to their new position.
	        link.transition()
	            .duration(duration)
	            .attr("d", diagonal)
	            .style("stroke", function(d) {
		            if (d.target.class === "found") {
		                return "#ff9999";
		            }
		        });

	        // Transition exiting nodes to the parent's new position.
	        link.exit().transition()
	            .duration(duration)
	            .attr("d", function(d) {
	                var o = {
	                    x: source.x,
	                    y: source.y
	                };
	                return diagonal({
	                    source: o,
	                    target: o
	                });
	            })
	            .remove();

	        // Stash the old positions for transition.
	        nodes.forEach(function(d) {
	            d.x0 = d.x;
	            d.y0 = d.y;
	        });
	    }

	    // Append a group which holds all nodes and which the zoom Listener can act upon.
	    var svgGroup = baseSvg.append("g");

	    // Define the root
	    root    = treeData;
	    root.x0 = viewerHeight / 2;
	    root.y0 = 0;

	    var hash = window.location.hash ? window.location.hash.substring(1) : "";
	    if (hash == 'blowup') {
	        document.getElementById("blowup").src = "images/collapse.png";
	        update(root);
	        alignNode(root, 'left');
	    } else if (hash && servers[hash]) {
	    	document.getElementById("blowup").src = "images/expand.png";

	        root.children.forEach(function(child){
	            collapse(child);
	        });

	        update(root, true);
	        alignNode(root, 'left');

	    	setTimeout(function() {
		        $("#servers").val(hash).trigger("chosen:updated");
		        $("#servers").change();
	      	}, 250);
	    } else {
	        document.getElementById("blowup").src = "images/expand.png";

	        root.children.forEach(function(child){
	            collapse(child);
	        });

	        root.class = "highlightLine";
	        update(root, true);
	        alignNode(root, 'left');
	        $("#servers").val('Database Servers').trigger("chosen:updated");

	        root.children.forEach(function(child) {
	            if (child.name == 'Production') {
	                setTimeout(function() {
	                    toggle(child);
	                    highlightLine(child);
	                    update(child);
	                    $("#servers").val('Production').trigger("chosen:updated");
	                }, 750);
	            }
	        });
	    }
	});

	function blowupNodes() {
	    var hash = window.location.hash ? window.location.hash.substring(1) : "";
	    if (hash == 'blowup') {
	        window.location.hash = '';
	    } else {
	        window.location.hash = 'blowup';
	    }

	    location.reload();
	}
</script>
</body>
