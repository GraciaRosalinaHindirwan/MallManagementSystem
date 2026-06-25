<?php

require '../../config/conn.php';

$page_title='Reminder Kontrak';
$active_page='notifikasi';
$user_name='Leasing Manager';
$role='leasingManager';


$q=mysqli_query($conn,"
SELECT

contract_number,
end_date,

DATEDIFF(end_date,CURDATE()) days

FROM 02_contracts

WHERE contract_status='Active'

AND DATEDIFF(end_date,CURDATE())<=30

ORDER BY end_date ASC

");


ob_start();

?>


<style>

.card{

background:#0B376D;
padding:30px;
border-radius:15px;

}


table{

width:100%;
border-collapse:collapse;

}


th,td{

padding:15px;

text-align:left;

border-bottom:1px solid rgba(255,255,255,.1);

}


.days{

color:#FFB62A;
font-weight:bold;

}

</style>




<div class="card">


<h2>Reminder Kontrak</h2>


<table>


<tr>

<th>Kontrak</th>
<th>Berakhir</th>
<th>Sisa Hari</th>

</tr>



<?php while($r=mysqli_fetch_assoc($q)):?>


<tr>

<td><?= $r['contract_number']?></td>

<td><?= $r['end_date']?></td>

<td class="days">

<?= $r['days']?> Hari

</td>

</tr>



<?php endwhile;?>


</table>


</div>



<?php

$content=ob_get_clean();

require '../../includes/navbarM02.php';

?>