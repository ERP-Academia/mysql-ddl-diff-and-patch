# mysql-ddl-diff-and-patch
Create a patch.sql to modify older database to newer one. Add missing tables or missing columns. Modify changed column types.

#Description

Edit `config.php`

Set the mysql user, pass, new_db and old_db name.

Run `php 0_copy_old_to_tmpolddatabase.php`

It will copy your old database to 'tmpolddatabase'

Run `php 1_dump_and_diff.php`

It will dump the new and the old databases. Then it will analyze the differences and 
generate file `patch.sql`

You can stop here or:

Run `php 2_apply_patch.php`

It will patch 'tmpolddatabase'

And finally `php 9_check.php`

It will dump three files:
```
Z_diff_new.sql
Z_diff_old_patched.sql ('tmpolddatabase')
Z_diff_old_not_patched.sql
```

You can use a diff program to compare the DDLs of the databases.
