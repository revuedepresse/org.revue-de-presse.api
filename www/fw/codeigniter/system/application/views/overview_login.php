<html>
<head>
<title>Overview</title>
</head>
<body>

<?php echo validation_errors(); ?>

<?php echo form_open('welcome'); ?>

<label>Username</label>
<input type="text" name="username" value="<?php echo set_value('username'); ?>" size="50" />

<label>Password</label>
<input type="password" name="password" value="<?php echo set_value('password'); ?>" size="50" />

<div><input type="submit" value="Submit" /></div>

</form>

</body>
</html>