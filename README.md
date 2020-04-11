# mysql-ddl-diff-and-patch
Create a patch.sql to modify older database to newer one. Add missing tables or missing columns. Modify changed column types.

# How it works

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

You can use a diff tool to compare the DDLs of the databases.

# Example

### Old DB structure
```sql
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `phone_work` varchar(30) NOT NULL DEFAULT '',
  `phone_home` varchar(30) NOT NULL DEFAULT '',
  `mobile` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name1` varchar(100) NOT NULL DEFAULT '',
  `name2` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### New DB structure
```sql
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mobile` varchar(100) DEFAULT NULL,
  `phone_work` varchar(100) NOT NULL DEFAULT '',
  `phone_home` varchar(100) NOT NULL DEFAULT '',
  `skype` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email_home` varchar(200) NOT NULL DEFAULT '',
  `email_work` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name1` varchar(100) NOT NULL DEFAULT '',
  `name2` varchar(100) NOT NULL DEFAULT '',
  `name3` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Generated patch

```sql
ALTER TABLE `contacts` DROP INDEX `id`;
ALTER TABLE `contacts` MODIFY `mobile` VARCHAR (100) DEFAULT NULL;
ALTER TABLE `contacts` MODIFY `phone_work` VARCHAR (100) NOT NULL DEFAULT '';
ALTER TABLE `contacts` MODIFY `phone_home` VARCHAR (100) NOT NULL DEFAULT '';
ALTER TABLE `contacts` ADD COLUMN `skype` VARCHAR (100) NOT NULL DEFAULT '' AFTER `phone_home`;
ALTER TABLE `contacts` MODIFY `mobile` VARCHAR (100) DEFAULT NULL AFTER `user_id`;
ALTER TABLE `contacts` MODIFY `phone_work` VARCHAR (100) NOT NULL DEFAULT '' AFTER `mobile`;
CREATE INDEX `user_id` ON `contacts` (`user_id`);
CREATE TABLE `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email_home` varchar(200) NOT NULL DEFAULT '',
  `email_work` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `users` ADD COLUMN `name3` VARCHAR (100) NOT NULL DEFAULT '' AFTER `name2`;
```
