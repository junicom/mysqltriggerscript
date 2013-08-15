mysqltriggerscript
==================

PHP script (with UI) to create triggers for chosen tables in a mysql database.
Possible triggers are:
- before update
- after update
- before delete
- after delete

The script creates duplicates of every table previously selected over the UI and sets a trigger on the original table to copy modified rows into the newly created history table.
Therefore, it is possible to set up a revision table to retrace the changes made on the original table.
Every row written in the history table has a timestamp and the mysql user, who performed the modification.

The script is very simple to use:
1. Copy the files to your server (where the mysql database is running) or use some external server, if your mysql server allows access from outside
2. Run the index.php and insert the required data to access to the database (check if the mysql user has the authority to create databases, tables and triggers)
3. Select the database where the desired tables are
4. Select the target database where to create the history tables (it is possible to create a new database within this step)
5. Select the time, the event and the desired tables to trigger
6. Press "Trigger erstellen" to create the necessary database, tables and triggers

After that, you will have a nice revision history.


