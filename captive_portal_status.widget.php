<?php
/*
 * captive_portal_status.widget.php
 *
 * Author: akosej9208@gmail.com
 */

require_once("globals.inc");
require_once("guiconfig.inc");
require_once("pfsense-utils.inc");
require_once("functions.inc");
require_once("captiveportal.inc");

init_config_arr(array('captiveportal'));
$a_cp = &$config['captiveportal'];

$cpzone = $_GET['zone'];
if (isset($_POST['zone'])) {
    $cpzone = $_POST['zone'];
}
$cpzone = strtolower($cpzone);

if (isset($cpzone) && !empty($cpzone) && isset($a_cp[$cpzone]['zoneid'])) {
    $cpzoneid = $a_cp[$cpzone]['zoneid'];
}

if (($_GET['act'] == "del") && !empty($cpzone) && isset($cpzoneid)) {
    captiveportal_disconnect_client($_GET['id'], 6);
}
unset($cpzone);

flush();

if (!function_exists('clientcmp')) {
    function clientcmp($a, $b) {
        global $order;
        return strcmp($a[$order], $b[$order]);
    }
}

$cpdb_all = array();

foreach ($a_cp as $cpzone => $cp) {
    $cpdb = captiveportal_read_db();
    foreach ($cpdb as $cpent) {
        $cpent[10] = $cpzone;
        $cpent[11] = captiveportal_get_last_activity($cpent[2]);
        $cpdb_all[] = $cpent;
    }
}

$num_users = count($cpdb_all); // Count the number of connected users

?>
<div class="table-responsive" style="height: 300px; overflow-y: auto;">
	<div class="row">
		<div class="col-md-3">&nbsp;<i class="fa fa-users" title="<?=gettext("Number of users connected");?>"></i>&nbsp; <?php echo $num_users; ?></div>
		<div class="col-md-1"><i class="fa fa-search"></i></div>
		<div class="col-md-6">
        	<input type="text" class="form-control" id="searchInput" placeholder="Search...">
		</div>
	</div>
	
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-default filter-button" date-filter="ip">Filter by IP</button>
        <button type="button" class="btn btn-default filter-button" date-filter="mac">Filter by MAC</button>
        <button type="button" class="btn btn-default filter-button" date-filter="user">Filter by Username</button>
    </div>
    <table class="table table-condensed sortable-theme-bootstrap" data-sortable>
        <thead>
        <tr>
            <th>IP</th>
            <th>MAC</th>
            <th>Username</th>
            <th>Session start</th>
            <th>Last activity</th>
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($cpdb_all)): ?>
            <tr>
                <td colspan="6">User not found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($cpdb_all as $cpent): ?>
                <tr data-ip="<?=$cpent[2];?>" data-mac="<?=$cpent[3];?>" date-user="<?=$cpent[4];?>">
                    <td><?=$cpent[2];?></td>
                    <td><?=$cpent[3];?></td>
                    <td><?=$cpent[4];?></td>
                    <td><?=date("m/d/Y H:i:s", $cpent[0]);?></td>
                    <td>
                        <?php
                        if ($cpent[11] && ($cpent[11] > 0)):
                            echo date("m/d/Y H:i:s",$cpent[11]);
                        else:
                            echo "-";
                        endif;
                        ?>
                    </td>
                    <td>
                        <a href="?order=<?=htmlspecialchars($_GET['order']);?>&amp;showact=<?=$showact;?>&amp;act=del&amp;zone=<?=$cpent[10];?>&amp;id=<?=$cpent[5];?>">
					        <i class="fa fa-sign-out-alt" title="<?=gettext("logout");?>"></i>
				        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        function updateNumUsers() {
            $.ajax({
                url: "captive_portal_status.widget.php",
                type: "GET",
                data: {
                    zone: "<?php echo htmlspecialchars($cpzone); ?>"
                },
                success: function(response) {
                    var num_users = $(response).find('#num_users').html();
                    $('#num_users').html(num_users);
                }
            });
        }

        // Call the updateNumUsers function every 5 seconds
        setInterval(updateNumUsers, 5000);

        // Filter the table rows based on user input
        $("#searchInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Filter the table rows based on the button clicked
        $(".filter-button").click(function() {
            var value = $(this).attr('date-filter');
            if (value == "all") {
                $("table tbody tr").show();
            } else {
                $("table tbody tr").each(function() {
                    if ($(this).attr('date-' + value).toLowerCase().indexOf(value) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
    });
</script>