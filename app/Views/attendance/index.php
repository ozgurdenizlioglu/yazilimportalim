<?php $this->layout('layouts/main'); ?>
<div class="container">
  <h1>Yoklama Kayıtları</h1>
  <table border="1" cellpadding="6" cellspacing="0">
    <thead>
      <tr>
        <th>Time</th>
        <th>User</th>
        <th>Type</th>
        <th>Source</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['scanned_at']) ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['type']) ?></td>
          <td><?= htmlspecialchars($r['source_device']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>