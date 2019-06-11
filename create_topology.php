<?php
/**
 * Database Topology Mapper is a tool that pairs master & slave servers into a beautiful map using D3.js
 * that is easily digestible for DBAs and anyone else in your organization.
 *
 * @author     Charles Thompson <01charles.t@gmail.com>
 * @copyright  2019 Charles Thompson
 * @license    http://opensource.org/licenses/MIT
 * @link       https://github.com/vuther/db-topology-mapper
 * @version    1.1
*/

 ///////////////////////////////////////////////////////
 //                Variables to Change                //
 ///////////////////////////////////////////////////////
$environments = array(
    "Production" => array('fill_color' => '#d8d844', 'stroke_color' => '#5d5d0b'),
    "QA"         => array('fill_color' => '#f9baff', 'stroke_color' => '#9d06aa'),
    "Test"       => array('fill_color' => '#cceeff', 'stroke_color' => '#005580'),
    "Dev"        => array('fill_color' => '#d4d4d4', 'stroke_color' => '#3c3c3c')
);

///////////////////////////////////////////////////////
//                   Begin script                    //
///////////////////////////////////////////////////////
$root_node           = 'Database Servers'; // First node on the map
$servers[$root_node] = array('master' => null); // Setup root node

// Save environments to map array
foreach ($environments as $environment_name => $environment_data) {
    $servers[$environment_name] = array('master' => "Database Servers");
}

// Read & decode JSON server list
$json_server_data = file_get_contents("server_list.json") or die("Cannot open file server_list.json!\n");
$server_data      = json_decode($json_server_data, true);

// Loop through json decoded array and store servers + their metadata
foreach ($server_data[0] as $key => $value) {
    $servers[$key] = array(
        "master"  => $value['master'],
        "role"    => $value['role'],
        "ip"      => $value['ip'],
        "version" => $value['version']
    );
}

// Add masters to servers list that do not exist so it can be visually shown
foreach ($servers as $server_name => $server_details) {
    if (!array_key_exists($server_details['master'], $servers)) {
        if (!$server_details['master']) continue;
        $servers[$server_details['master']] = array('master'  => "Database Servers");
    }
}

// Re-sort the list of server names as nested arrays indicating the hierarchy
foreach ($servers as $server_name => $server_details) {
    if ($server_details['master']) {
        //Assign by reference so servers of this server will also appear further down the same branch
        $servers[$server_details['master']]['servers'][$server_name] = &$servers[$server_name];
    }
}

// Since the $servers array is now a mix of relative hiearchical relationships and an unsorted list,
// We have to weed out the latter.  Start by identifying the people at the top
$hierarchy = array();
foreach ($servers as $server_name => $server_details) {
    if (!isset($server_details['master']) || is_null($server_details['master'])) {
        $hierarchy[$server_name] = array();
    }
}

// Now, recursively follow the branches down through the 'server' arrays
foreach ($hierarchy as $server_name => &$server_details) {
    growTree($server_name, $server_details);
}
unset($server_details);

$treeData = array();
foreach ($hierarchy as $server_name => $server_details) {
    // There should only be one top-level element in $hierarchy at this point
    $treeData = formatData($server_name, $server_details);
}

foreach ($servers as $server_name => $server_details) {
    unset($servers[$server_name]['servers']);
}

$server_names = array_keys($servers);
sort($server_names);

$last_updated = "Last updated: " . date('F d, Y g:i:sa');

// JSON encode our hierarchical array for D3
$data = array($treeData, $servers, $server_names, $environments, $last_updated);
print json_encode($data);
// file_put_contents("", json_encode($data));

function growTree($branch_id, &$branch) {
    global $servers;
    $server_details = $servers[$branch_id];
    if (isset($server_details['servers'])) {
        $server_names = array_keys($server_details['servers']);
        $servers_placeholders = array_pad(array(), count($server_names), array());
        $servers_placeholders = array_combine($server_names, $servers_placeholders);
    }

    $branch = $server_details;

    if (isset($branch['servers'])) {
        $branch['servers'] = $servers_placeholders;
        foreach ($branch['servers'] as $server_name => &$server_details) {
            growTree($server_name, $server_details);
        }
    }
    unset($server_details);
}

function formatData($server_name, $branch) {
    global $environments, $root_node;
    $html = '';

    if (!isset($branch['version'])) $branch['version'] = 'N/A';
    if (!isset($branch['role']))  $branch['role'] = '';

    if (!array_key_exists($server_name, $environments) && $server_name != $root_node) {
        $html = <<<HTML
            <div class="bubble_header">
                <div class="bubble_header_text">$server_name ({$branch['ip']})</div>
            </div>
            <div class="bubble_text_version">{$branch['version']}</div>
            <b><div class="bubble_text_uptime">{$branch['role']}</div></b>
HTML;
    } else if ($server_name == 'Database Servers') {
        $html = <<<HTML
            <img class="database_image" src="images/database.png"></img>
HTML;
    } else {
        $html = <<<HTML
            <div class="root_node">$server_name</div>
HTML;
    }

    // Determine fill/stroke color depending on environment
    if (array_key_exists($server_name, $environments)) {
        $fill_color   = $environments[$server_name]['fill_color'];
        $stroke_color = $environments[$server_name]['stroke_color'];
    } else {
        $fill_color   = 'white';
        $stroke_color = '#008000';
    }

    $formatted = array(
        "name"           => $server_name,
        "content"        => $html,
        "fill_color"     => $fill_color,
        "stroke_color"   => $stroke_color,
        "children"       => array()
    );

    if (isset($branch['servers'])) {
        foreach ($branch['servers'] as $server_name => $server_details) {
            $formatted['children'][] = formatData($server_name, $server_details);
        }
    }

    unset($branch['servers']);
    foreach ($branch as $key => $val) {
        $formatted[$key] = $val;
    }
    if (empty($formatted['children'])) {
        unset($formatted['children']);
    }
    return $formatted;
}
?>
