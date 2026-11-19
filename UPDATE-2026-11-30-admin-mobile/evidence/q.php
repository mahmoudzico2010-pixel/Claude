<?php $d = new PDO('sqlite:'.$argv[1]); $s = $d->query($argv[2]); echo json_encode($s ? $s->fetchAll(PDO::FETCH_ASSOC) : null);
