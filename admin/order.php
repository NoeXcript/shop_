<?php require_once('header.php'); ?>

<?php
$error_message = '';
if(isset($_POST['form1'])) {
    $valid = 1;
    if(empty($_POST['subject_text'])) {
        $valid = 0;
        $error_message .= 'O assunto não pode ficar vazio\n';
    }
    if(empty($_POST['message_text'])) {
        $valid = 0;
        $error_message .= 'O assunto não pode ficar vazio\n';
    }
    if($valid == 1) {

        $subject_text = strip_tags($_POST['subject_text']);
        $message_text = strip_tags($_POST['message_text']);

        // Getting Customer Email Address
        $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=?");
        $statement->execute(array($_POST['cust_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $cust_email = $row['cust_email'];
        }

        // Getting Admin Email Address
        $statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $admin_email = $row['contact_email'];
        }

        $order_detail = '';
        $statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_id=?");
        $statement->execute(array($_POST['payment_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
        	
        	if($row['payment_method'] == 'PayPal'):
        		$payment_details = '
ID Transacção: '.$row['txnid'].'<br>
        		';
        	elseif($row['payment_method'] == 'Stripe'):
				$payment_details = '
ID Transacção: '.$row['txnid'].'<br>
Número do cartão: '.$row['card_number'].'<br>
Cartão CVV: '.$row['card_cvv'].'<br>
Mês Cartão: '.$row['card_month'].'<br>
Ano Cartão: '.$row['card_year'].'<br>
        		';
        	elseif($row['payment_method'] == 'Bank Deposit'):
				$payment_details = '
Detalhes da Transacção: <br>'.$row['bank_transaction_info'];
        	endif;

            $order_detail .= '
Nome do cliente: '.$row['customer_name'].'<br>
E-mail do cliente: '.$row['customer_email'].'<br>
Método de pagamento: '.$row['payment_method'].'<br>
Data do pagamento: '.$row['payment_date'].'<br>
Detalhe do pagamento: <br>'.$payment_details.'<br>
Quantia pago: '.$row['paid_amount'].'<br>
Estadod pagamento: '.$row['payment_status'].'<br>
Estado do frete: '.$row['shipping_status'].'<br>
ID do pagamento: '.$row['payment_id'].'<br>
            ';
        }

        $i=0;
        $statement = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
        $statement->execute(array($_POST['payment_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $i++;
            $order_detail .= '
<br><b><u>Productos '.$i.'</u></b><br>
Nome do producto: '.$row['product_name'].'<br>
Quantidade: '.$row['quantity'].'<br>
Preço unitário: '.$row['unit_price'].'<br>
            ';
        }

        $statement = $pdo->prepare("INSERT INTO tbl_customer_message (subject,message,order_detail,cust_id) VALUES (?,?,?,?)");
        $statement->execute(array($subject_text,$message_text,$order_detail,$_POST['cust_id']));

        // sending email
        $to_customer = $cust_email;
        $message = '
<html><body>
<h3>Mensagem: </h3>
'.$message_text.'
<h3>Detalhe da encomenda: </h3>
'.$order_detail.'
</body></html>
';
        $headers = 'From: ' . $admin_email . "\r\n" .
                   'Reply-To: ' . $admin_email . "\r\n" .
                   'X-Mailer: PHP/' . phpversion() . "\r\n" . 
                   "MIME-Version: 1.0\r\n" . 
                   "Content-Type: text/html; charset=ISO-8859-1\r\n";

        // Sending email to admin                  
        mail($to_customer, $subject_text, $message, $headers);
        
        $success_message = 'Seu e-mail para o cliente foi enviado com sucesso.';

    }
}
?>
<?php
if($error_message != '') {
    echo "<script>alert('".$error_message."')</script>";
}
if($success_message != '') {
    echo "<script>alert('".$success_message."')</script>";
}
?>

<section class="content-header">
	<div class="content-header-left">
		<h1>Ver encomendas</h1>
	</div>
</section>


<section class="content">

  <div class="row">
    <div class="col-md-12">


      <div class="box box-info">
        
        <div class="box-body table-responsive">
          <table id="example1" class="table table-bordered table-hover table-striped">
			<thead>
			    <tr>
			        <th>[ID]</th>
                    <th>Cliente</th>
			        <th>Detalhes do produto</th>
                    <th>
                    Informação de pagamento
                    </th>
                    <th>Quantidade paga</th>
                    <th>Status do pagamento</th>
                    <th>Status de envio</th>
			        <th>Operação</th>
			    </tr>
			</thead>
            <tbody>
            	<?php
            	$i=0;
            	$statement = $pdo->prepare("SELECT * FROM tbl_payment ORDER by id DESC");
            	$statement->execute();
            	$result = $statement->fetchAll(PDO::FETCH_ASSOC);							
            	foreach ($result as $row) {
            		$i++;
            		?>
					<tr class="<?php if($row['payment_status']=='Pending'){echo 'bg-r';}else{echo 'bg-g';} ?>">
	                    <td><?php echo $i; ?></td>
	                    <td>
                            <b>ID:</b> <?php echo $row['customer_id']; ?><br>
                            <b>Nome:</b><br> <?php echo $row['customer_name']; ?><br>
                            <b>E-mail:</b><br> <?php echo $row['customer_email']; ?><br><br>
                            <a href="#" data-toggle="modal" data-target="#model-<?php echo $i; ?>"class="btn btn-warning btn-xs" style="width:100%;margin-bottom:4px;">Enviar mensagem</a>
                            <div id="model-<?php echo $i; ?>" class="modal fade" role="dialog">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal">&times;</button>
											<h4 class="modal-title" style="font-weight: bold;">Enviar mensagem</h4>
										</div>
										<div class="modal-body" style="font-size: 14px">
											<form action="" method="post">
                                                <input type="hidden" name="cust_id" value="<?php echo $row['customer_id']; ?>">
                                                <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
												<table class="table table-bordered">
													<tr>
														<td>Assunto</td>
														<td>
                                                            <input type="text" name="subject_text" class="form-control" style="width: 100%;">
														</td>
													</tr>
                                                    <tr>
                                                        <td>Mensagem</td>
                                                        <td>
                                                            <textarea name="message_text" class="form-control" cols="30" rows="10" style="width:100%;height: 200px;"></textarea>
                                                        </td>
                                                    </tr>
													<tr>
														<td></td>
														<td><input type="submit" value="Enviar mensagem" name="form1"></td>
													</tr>
												</table>
											</form>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
										</div>
									</div>
								</div>
							</div>
                        </td>
                        <td>
                           <?php
                           $statement1 = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
                           $statement1->execute(array($row['payment_id']));
                           $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
                           foreach ($result1 as $row1) {
                                echo '<b>Produto:</b> '.$row1['product_name'];
                                // echo '<br>(<b>Size:</b> '.$row1['size'];
                                // echo ', <b>Color:</b> '.$row1['color'].')';
                                echo '<br>(<b>Quantity:</b> '.$row1['quantity'];
                                echo ', <b>Unit Price:</b> '.$row1['unit_price'].')';
                                echo '<br><br>';
                           }
                           ?>
                        </td>
                        <td>
                        	<?php if($row['payment_method'] == 'PayPal'): ?>
                        		<b>Método de pagamento:</b> <?php echo '<span style="color:red;"><b>'.$row['payment_method'].'</b></span>'; ?><br>
                        		<b>ID do Pagamento:</b> <?php echo $row['payment_id']; ?><br>
                        		<b>Data:</b> <?php echo $row['payment_date']; ?><br>
                        		<b>ID de transacção:</b> <?php echo $row['txnid']; ?><br>
                        	<?php elseif($row['payment_method'] == 'Stripe'): ?>
                        		<b>Método de pagamento:</b> <?php echo '<span style="color:red;"><b>'.$row['payment_method'].'</b></span>'; ?><br>
                        		<b>ID de pagamento:</b> <?php echo $row['payment_id']; ?><br>
								<b>Data:</b> <?php echo $row['payment_date']; ?><br>
                        		<b>ID transacção:</b> <?php echo $row['txnid']; ?><br>
                        		<b>Número de carão:</b> <?php echo $row['card_number']; ?><br>
                        		<b>Cartão CVV:</b> <?php echo $row['card_cvv']; ?><br>
                        		<b>Mês da expiração:</b> <?php echo $row['card_month']; ?><br>
                        		<b>Ano de expiração:</b> <?php echo $row['card_year']; ?><br>
                        	<?php elseif($row['payment_method'] == 'Bank Deposit'): ?>
                        		<b>Método de pagamento:</b> <?php echo '<span style="color:red;"><b>'.$row['payment_method'].'</b></span>'; ?><br>
                        		<b>ID do pagamento:</b> <?php echo $row['payment_id']; ?><br>
								<b>Data:</b> <?php echo $row['payment_date']; ?><br>
                        		<b>Informações de transação:</b> <br><?php echo $row['bank_transaction_info']; ?><br>
                        	<?php endif; ?>
                        </td>
                        <td>$<?php echo $row['paid_amount']; ?></td>
                        <td>
                            <?php echo $row['payment_status']; ?>
                            <br><br>
                            <?php
                                if($row['payment_status']=='Pending'){
                                    ?>
                                    <a href="order-change-status.php?id=<?php echo $row['id']; ?>&task=Completed" class="btn btn-success btn-xs" style="width:100%;margin-bottom:4px;">Marcar como concluído</a>
                                    <?php
                                }
                            ?>
                        </td>
                        <td>
                            <?php echo $row['shipping_status']; ?>
                            <br><br>
                            <?php
                            if($row['payment_status']=='Completed') {
                                if($row['shipping_status']=='Pending'){
                                    ?>
                                    <a href="shipping-change-status.php?id=<?php echo $row['id']; ?>&task=Completed" class="btn btn-warning btn-xs" style="width:100%;margin-bottom:4px;">Marcar como concluído</a>
                                    <?php
                                }
                            }
                            ?>
                        </td>
	                    <td>
                            <a href="#" class="btn btn-danger btn-xs" data-href="order-delete.php?id=<?php echo $row['id']; ?>" data-toggle="modal" data-target="#confirm-delete" style="width:100%;">Eliminar</a>
	                    </td>
	                </tr>
            		<?php
            	}
            	?>
            </tbody>
          </table>
        </div>
      </div>
  

</section>


<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Confirmação de eliminação</h4>
            </div>
            <div class="modal-body">
            Tem certeza de que deseja excluir este item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <a class="btn btn-danger btn-ok">Eliminar</a>
            </div>
        </div>
    </div>
</div>


<?php require_once('footer.php'); ?>