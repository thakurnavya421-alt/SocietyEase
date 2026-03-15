<?php
include "config.php";
include "header.php";
include "sidebar.php";

if(isset($_POST['add'])){
mysqli_query($conn,"INSERT INTO notices(title,description,created_by)
VALUES('$_POST[title]','$_POST[desc]',{$_SESSION['uid']})");
}
?>
<form method="post">
<input name="title" class="form-control mb-2" placeholder="Title">
<textarea name="desc" class="form-control mb-2"></textarea>
<button name="add" class="btn btn-primary">Add</button>
</form>
<?php include "footer.php"; ?>
