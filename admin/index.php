<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>GWI WX admin tool</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
        <meta http-equiv="pragma" content="no-cache" />
        <link rel="stylesheet" type="text/css" href="../css/admin_table.css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    </head>
    <body>
                    <a name="top"></a>
        <div style="text-align: center;">
            <a href="#sig_wx_edit">To SIG WX Editor</a>
            <form action="add.php" method="POST" name="add_form" id="add_form">
                ADD NEW STATION - <input type="text" size="5" name="station" id="station"/>
                <input type="submit" value="Add"/>
            </form>
        </div>
        <table align="center">
            <tr>
                <th>Station</th>
                <th><span style="color:#FFD800">Ceiling</span></th>
                <th><span style="color:#FFD800">Visibility</span></th>
                <th><span style="color:#FFD800">Wind</span></th>
                <th><span style="color:#F20056">Ceiling</span></th>
                <th><span style="color:#F20056">Visibility</span></th>
                <th><span style="color:#F20056">Wind</span></th>
                <th class="winter">Destination W</th>
                <th class="winter">Active W</th>
                <th class="summer">Destination S</th>
                <th class="summer">Active S</th>
                <th><img src="../img/delete.png"></th>
            </tr>
            <?php
            include "../include/config.php";
            $ap_array = array();
            mysql_connect($mysql_host, $mysql_user, $mysql_password);
            mysql_select_db($mysql_db);
            $stations = mysql_query('SELECT * FROM german_wings_stations ORDER BY  `active` DESC ,  `station` ASC ');
            while ($row = mysql_fetch_assoc($stations)) {
            // Destination columg values reversed since SQl column is alternate indicatorw
            ?>
                <form action="#" name="<?=$row['station'];?>" id="<?=$row['station'];?>">
                    <tr>
                        <td><?=$row['station']; ?></td>
                        <td name="<?=$row['station'];?>">
                        	<input class="value_change" size="5" type="text" name="2_ceil" value="<?= $row['2_ceil']; ?>" />
                        </td>
                        <td name="<?=$row['station'];?>">
                        	<input class="value_change" size="5" type="text" name="2_vis" value="<?= $row['2_vis']; ?>" />
                        </td>
                        <td name="<?=$row['station'];?>">
                        	<input class="value_change" size="5" type="text" name="2_wind" value="<?= $row['2_wind']; ?>" />
                        </td>
                        <td name="<?=$row['station'];?>">	
                        	<input class="value_change" size="5" type="text" name="3_ceil" value="<?= $row['3_ceil']; ?>" />
                        </td>
                        <td name="<?=$row['station'];?>">
                        	<input class="value_change" size="5" type="text" name="3_vis" value="<?= $row['3_vis']; ?>" />
                        </td>
                        <td name="<?=$row['station'];?>">
                        	<input class="value_change" size="5" type="text" name="3_wind" value="<?= $row['3_wind']; ?>" />
                        </td>
                        <td name="<?=$row['station'];?>" class="winter">
                        	<input class="value_change_chk_alt" name="alternate" value="<?= $row['alternate']; ?>" type="checkbox" <?= ($row['alternate'] == '0' ? 'checked' : '') ?> />
                        </td>
                        <td name="<?=$row['station'];?>"  class="winter">
                        	<input class="value_change_chk" name="active" value="<?= $row['active']; ?>" type="checkbox" <?= ($row['active'] == '1' ? 'checked' : '') ?> />
                        </td>
                        <td name="<?=$row['station'];?>"  class="summer">
                        	<input class="value_change_chk_alt" name="alternate_s" value="<?= $row['alternate+_s']; ?>" type="checkbox" <?= ($row['alternate_s'] == '0' ? 'checked' : '') ?> />
                        </td>
                        <td name="<?=$row['station'];?>"  class="summer">
                        	<input class="value_change_chk" name="active_s" value="<?= $row['active_s']; ?>" type="checkbox" <?= ($row['active_s'] == '1' ? 'checked' : '') ?> />
                        </td>
                        <td><a href="delete.php?station=<?=$row['station'];?>"><img src="../img/delete.png"></a></td>
                    </tr>
                </form>
            <?
            }
            //mysql_close();
            ?>
        </table>
        <br/>
            <?
                $sig_wx_y = mysql_query('SELECT * FROM german_wings_settings where `setting` = "sig_wx_y" LIMIT 1');
                while($row =  mysql_fetch_assoc($sig_wx_y)){
                    $severe_wx_y = $row['value'];
                }
				$sig_wx_r = mysql_query('SELECT * FROM german_wings_settings where `setting` = "sig_wx_r" LIMIT 1');
                while($row =  mysql_fetch_assoc($sig_wx_r)){
                    $severe_wx_r = $row['value'];
                }
                mysql_close();
            ?>
        <div style="text-align:center;" id="significant_wx">
            <a href="#top">To TOP</a><br/><br/>
            <a name="sig_wx_edit"><span style="font-weight:bold;font-size: 18px;">Significant weather editor YELLOW:</span></a>
            <form action="#" name="form_sig_wx_y" id="form_sig_wx_y" method="POST">
                <textarea id="sig_wx_y" name="sig_wx_y" cols="70" rows="10"><?=$severe_wx_y;?></textarea>
            </form>
        </div>
		<div style="text-align:center;" id="significant_wx">
            <a href="#top">To TOP</a><br/><br/>
            <a name="sig_wx_edit"><span style="font-weight:bold;font-size: 18px;">Significant weather editor RED:</span></a>
            <form action="#" name="form_sig_wx_r" id="form_sig_wx_r" method="POST">
                <textarea id="sig_wx_r" name="sig_wx_r" cols="70" rows="10"><?=$severe_wx_r;?></textarea>
            </form>
        </div>
        <br/>
        <script>
            $(".value_change").change(function() {
                $.post("edit.php", { station: $(this).parent().attr('name'),
                                    column: $(this).attr('name'),
                                    value: $(this).val() } );
            });
            $(".value_change_chk").change(function() {
                value_to_set = 1;
                if($(this).is(':checked')){
                    value_to_set = 1;
                }else{
                    value_to_set = 0;
                }
                //alert($(this).parent().attr('name')+" "+$(this).attr('name')+" "+$(this).val());
                $.post("edit.php", { station: $(this).parent().attr('name'),
                                    column: $(this).attr('name'),
                                    value: value_to_set } );
            });
            $(".value_change_chk_alt").change(function() {
                value_to_set = 0;
                if($(this).is(':checked')){
                    value_to_set = 0;
                }else{
                    value_to_set = 1;
                }
                //alert($(this).parent().attr('name')+" "+$(this).attr('name')+" "+$(this).val());
                $.post("edit.php", { station: $(this).parent().attr('name'),
                                    column: $(this).attr('name'),
                                    value: value_to_set } );
            });
            $("#sig_wx_y").keyup(function(){
                $.post("edit_sigwx.php", { setting: 'sig_wx_y',
                                        value: $(this).val() } );
            });
			$("#sig_wx_r").keyup(function(){
                $.post("edit_sigwx.php", { setting: 'sig_wx_r',
                                        value: $(this).val() } );
            });
        </script>
    </body>
</html>