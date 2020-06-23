<?php
	$this->render('incs/head', ['title' => 'Webhooks - Add'])
?>
<div id="wrapper">
<?php
	$this->render('incs/nav', ['page' => 'webhooks'])
?>
	<div id="page-wrapper">
		<div class="container-fluid">
			<!-- Page Heading -->
			<div class="row">
				<div class="col-lg-12">
					<h1 class="page-header">
						Nouveau webhook
					</h1>
					<ol class="breadcrumb">
						<li>
							<i class="fa fa-dashboard"></i> <a href="<?php echo \descartes\Router::url('Dashboard', 'show'); ?>">Dashboard</a>
						</li>
						<li>
							<i class="fa fa-plug"></i> <a href="<?php echo \descartes\Router::url('Webhook', 'list'); ?>">Webhooks</a>
						</li>
						<li class="active">
							<i class="fa fa-plus"></i> Nouveau
						</li>
					</ol>
				</div>
			</div>
			<!-- /.row -->

			<div class="row">
				<div class="col-lg-12">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title"><i class="fa fa-plug fa-fw"></i> Ajout d'un nouveau webhook</h3>
						</div>
						<div class="panel-body">
							<form action="<?php echo \descartes\Router::url('Webhook', 'create', ['csrf' => $_SESSION['csrf']]);?>" method="POST">
								<div class="form-group">
									<label>URL cible</label>
									<div class="form-group">
										<input name="url" class="form-control" type="text" placeholder="http://example.fr/webhook/" autofocus required>
									</div>
								</div>	
								<div class="form-group">
									<label>Type de Webhook</label>
									<select name="type" class="form-control" required>
                                        <option value="receive_sms">Réception d'un SMS</option>
                                        <option value="send_sms">Envoi d'un SMS</option>
									</select>
								</div>	
								<a class="btn btn-danger" href="<?php echo \descartes\Router::url('Webhook', 'list'); ?>">Annuler</a>
								<input type="submit" class="btn btn-success" value="Enregistrer le webhook" /> 	
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
    $this->render('incs/footer');
