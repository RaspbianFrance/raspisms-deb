<?php
	//Template dashboard
    if ($is_unread)
    {
        $this->render('incs/head', ['title' => 'Receiveds - Unread']);
    }
    else
    {
        $this->render('incs/head', ['title' => 'Receiveds - Show All']);
    }
?>
<div id="wrapper">
<?php
	$this->render('incs/nav', ['page' => ($is_unread ? 'receiveds_unread' : 'receiveds')])
?>
	<div id="page-wrapper">
		<div class="container-fluid">
			<!-- Page Heading -->
			<div class="row">
				<div class="col-lg-12">
					<h1 class="page-header">
						Dashboard <small>SMS reçus</small>
					</h1>
					<ol class="breadcrumb">
						<li>
							<i class="fa fa-dashboard"></i> <a href="<?php echo \descartes\Router::url('Dashboard', 'show'); ?>">Dashboard</a>
						</li>
						<li class="active">
                            <i class="fa <?= $is_unread ? 'fa-eye-slash' : 'fa-download' ?> "></i> <?= $is_unread ? 'SMS non lus' : 'SMS reçus' ?>
						</li>
					</ol>
				</div>
			</div>
			<!-- /.row -->

			<div class="row">
				<div class="col-lg-12">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title"><i class="fa fa-fw <?= $is_unread ? 'fa-eye-slash' : 'fa-download' ?>"></i> <?= $is_unread ? 'Liste des SMS non lus' : 'Liste des SMS reçus' ?></h3>
						</div>
                        <div class="panel-body">
                            <form method="GET">
                                    <div class="table-receiveds">
                                        <table class="table table-bordered table-hover table-striped datatable" id="table-receiveds">
                                            <thead>
                                                <tr>
                                                    <th>Expéditeur</th>
                                                    <th>Destinataire</th>
                                                    <th>Message</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Commande</th>
                                                    <th class="checkcolumn"><input type="checkbox" id="check-all"/></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div>
                                        <div class="text-right col-xs-12 no-padding">
                                            <strong>Action pour la séléction :</strong>
                                            <button class="btn btn-default" type="submit" formaction="<?php echo \descartes\Router::url('Received', 'mark_as', ['status' => \models\Received::STATUS_READ, 'csrf' => $_SESSION['csrf']]); ?>"><span class="fa fa-eye"></span> Marquer comme lu</button>
                                            <button class="btn btn-default" type="submit" formaction="<?php echo \descartes\Router::url('Received', 'mark_as', ['status' => \models\Received::STATUS_UNREAD, 'csrf' => $_SESSION['csrf']]); ?>"><span class="fa fa-eye-slash"></span> Marquer comme non lu</button>
                                            <button class="btn btn-default btn-confirm" type="submit" formaction="<?php echo \descartes\Router::url('Received', 'delete', ['csrf' => $_SESSION['csrf']]); ?>"><span class="fa fa-trash-o"></span> Supprimer</button>
                                        </div>
                                    </div>
                            </form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
jQuery(document).ready(function ()
{
    jQuery('.datatable').DataTable({
        "pageLength": 25,
        "lengthMenu": [[25, 50, 100, 1000, 10000, Math.pow(10, 10)], [25, 50, 100, 1000, 10000, "All"]],
        "language": {
            "url": HTTP_PWD + "/assets/js/datatables/french.json",
        },
        "orderMulti": false,
        "order": [[3, "desc"]],
        "columnDefs": [{
            'targets': 'checkcolumn',
            'orderable': false,
        }],
        "serverSide": true,
        "ajax": {
            'url': '<?php echo $is_unread ? \descartes\Router::url('Received', 'list_json', ['unread' => true]) : \descartes\Router::url('Received', 'list_json'); ?>',
            'dataSrc': 'data',
        },
        "columns" : [
            {
                data: 'origin',
                render: function (data, type, row, meta) {
                    if (row.contact_name) {
                        return row.origin_formatted + ' (' + jQuery.fn.dataTable.render.text().display(row.contact_name) + ')';
                    }

                    return row.origin_formatted;
                },
            },
            {data: 'phone_name', render: jQuery.fn.dataTable.render.text()},
            {
                data: 'text',
                render: function (data, type, row, meta) {
                    if (row.mms == 1) {
                        var medias = [];
                        for (i = 0; i < row.medias.length; i++) {
                            medias.push('<a href="' + HTTP_PWD + '/data/public/' + jQuery.fn.dataTable.render.text().display(row.medias[i].path) + '" target="_blank">Fichier ' + (i + 1) + '</a>');
                        }
                        html = jQuery.fn.dataTable.render.text().display(data) + '<br/>' + medias.join(' - ');
                        return html;
                    }

                    return jQuery.fn.dataTable.render.text().display(data);
                },
            },
            {data: 'at', render: jQuery.fn.dataTable.render.text()},
            {
                data: 'status',
                render: function (data, type, row, meta) {
                    switch (data) {
                        case 'read':
                            return 'Lu';
                            break;

                        default:
                            return 'Non lu';
                    }
                },
            },
            {
                data: 'command', 
                render: function (data, type, row, meta) {
                    if (data == 0) {
                        return "Non";
                    } else {
                        return 'Oui';
                    }
                },
            },
            {
                data: 'id',
                render: function (data, type, row, meta) {
                    return '<input name="ids[]" type="checkbox" value="' + data + '">';
                },
            },
        ],
        "deferRender": true
    });

});
</script>
<?php
	$this->render('incs/footer');
