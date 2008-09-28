insert into sys_pages_items_history select date_format(dateCreated,"%Y") as dateCon,itemId,3,count(itemId)  
from sys_pages_items_hit group by dateCon,itemId;
insert into sys_pages_items_history select date_format(dateCreated,"%Y") as dateCon,itemId,4,count(itemId)  
from sys_pages_items_hit where userId>0 group by dateCon,itemId;

insert into sys_pages_items_history select date_format(dateCreated,"%Y-%m") as dateCon,itemId,3,count(itemId)  
from sys_pages_items_hit group by dateCon,itemId;
insert into sys_pages_items_history select date_format(dateCreated,"%Y-%m") as dateCon,itemId,4,count(itemId)  
from sys_pages_items_hit where userId>0 group by dateCon,itemId;

insert into sys_pages_items_history select date_format(dateCreated,"%Y-%m-%d") as dateCon,itemId,3,count(itemId)  
from sys_pages_items_hit group by dateCon,itemId;
insert into sys_pages_items_history select date_format(dateCreated,"%Y-%m-%d") as dateCon,itemId,4,count(itemId)  
from sys_pages_items_hit where userId>0 group by dateCon,itemId;

insert into sys_pages_items_history select date_format(dateCreated,"%Y-W%U") as dateCon,itemId,3,count(itemId)  
from sys_pages_items_hit group by dateCon,itemId;
insert into sys_pages_items_history select date_format(dateCreated,"%Y-W%U") as dateCon,itemId,4,count(itemId)  
from sys_pages_items_hit where userId>0 group by dateCon,itemId;

/* tags */
insert into sys_pages_items_history select date_format(dateCreated,"%Y") as dateCon,itemId,1,count(itemId)  
from sys_pages_items_tag where weight > 0 group by dateCon,itemId;

insert into sys_pages_items_history select date_format(dateCreated,"%Y-%m") as dateCon,itemId,1,count(itemId)  
from sys_pages_items_tag where weight > 0 group by dateCon,itemId;

insert into sys_pages_items_history select date_format(dateCreated,"%Y-%m-%d") as dateCon,itemId,1,count(itemId)  
from sys_pages_items_tag where weight > 0 group by dateCon,itemId;

insert into sys_pages_items_history select date_format(dateCreated,"%Y-W%U") as dateCon,itemId,1,count(itemId)  
from sys_pages_items_tag where weight > 0 group by dateCon,itemId;
