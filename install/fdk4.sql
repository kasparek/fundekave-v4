 ENGINE=InnoDB 

alter table sys_users_post add CONSTRAINT FK_post_1 FOREIGN KEY (postIdFrom) REFERENCES sys_users_post (postId);
alter table sys_users_post add CONSTRAINT FK_post_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_post add CONSTRAINT FK_post_3 FOREIGN KEY (userIdFrom) REFERENCES sys_users (userId);
alter table sys_users_post add CONSTRAINT FK_post_4 FOREIGN KEY (userIdTo) REFERENCES sys_users (userId);
alter table sys_users_draft add CONSTRAINT FK_user_draft FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_pages add CONSTRAINT FK_sys_pages_1 FOREIGN KEY (categoryId) REFERENCES sys_pages_category (categoryId);
alter table sys_pages add CONSTRAINT FK_sys_pages_2 FOREIGN KEY (pageIdTop) REFERENCES sys_pages (pageId);
alter table sys_poll add CONSTRAINT FK_sys_poll_2 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_poll add CONSTRAINT FK_sys_poll_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_poll_answers add CONSTRAINT FK_ankodp_1 FOREIGN KEY (pollId) REFERENCES sys_poll (pollId);
alter table sys_poll_answers_users add CONSTRAINT FK_ankklik_3 FOREIGN KEY (pollId) REFERENCES sys_poll (pollId);
alter table sys_poll_answers_users add CONSTRAINT FK_ankklik_2 FOREIGN KEY (pollAnswerId) REFERENCES sys_poll_answers (pollAnswerId);
alter table sys_poll_answers_users add CONSTRAINT FK_ankklik_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);

alter table sys_pages_items add CONSTRAINT FK_sys_pages_items_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_items add CONSTRAINT FK_sys_pages_items_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_pages_items add CONSTRAINT FK_sys_pages_items_3 FOREIGN KEY (itemIdTop) REFERENCES sys_pages_items (itemId);

alter table sys_banner add CONSTRAINT FK_banner_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_banner_hit add CONSTRAINT FK_banner_hit_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_banner_hit add CONSTRAINT FK_banner_hit_2 FOREIGN KEY (bannerId) REFERENCES sys_banner (bannerId);
alter table sys_pages_counter add CONSTRAINT FK_sys_pages_counter_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_counter add CONSTRAINT FK_sys_pages_counter_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_diary add CONSTRAINT FK_sys_users_diary_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);

alter table sys_pages_items_hit add CONSTRAINT FK_sys_pages_items_hit_1 FOREIGN KEY (itemId) REFERENCES sys_pages_items (itemId);
alter table sys_pages_items_hit add CONSTRAINT FK_sys_pages_items_hit_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_pages_items_tag add CONSTRAINT FK_sys_pages_items_tag_1 FOREIGN KEY (itemId) REFERENCES sys_pages_items (itemId);
alter table sys_pages_items_tag add CONSTRAINT FK_sys_pages_items_tag_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);

alter table sys_surfinie add CONSTRAINT FK_sys_surfinie_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_surfinie add  CONSTRAINT FK_sys_surfinie_2 FOREIGN KEY (categoryId) REFERENCES sys_pages_category (categoryId);
alter table sys_menu add CONSTRAINT FK_menu_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_favorites add CONSTRAINT FK_sys_pages_favorites_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_favorites add CONSTRAINT FK_sys_pages_favorites_2 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_friends add CONSTRAINT FK_sys_users_friends_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_friends add CONSTRAINT FK_sys_users_friends_2 FOREIGN KEY (userIdFriend) REFERENCES sys_users (userId);
alter table sys_leftpanel add CONSTRAINT FK_sys_leftpanel_1 FOREIGN KEY (functionId) REFERENCES sys_leftpanel_functions (functionId);
alter table sys_menu_secondary add CONSTRAINT FK_sys_menu_secondary_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_skin add CONSTRAINT FK_sys_skin_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_buttons add CONSTRAINT FK_sys_users_buttons_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_logged add CONSTRAINT FK_sys_users_logged_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_users_perm add CONSTRAINT FK_sys_users_perm_2 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_users_perm add CONSTRAINT FK_sys_users_perm_1 FOREIGN KEY (userId) REFERENCES sys_users (userId);
alter table sys_events add CONSTRAINT FK_sys_events_1 FOREIGN KEY (placeId) REFERENCES sys_events_place (placeId);
alter table sys_events add CONSTRAINT FK_sys_events_2 FOREIGN KEY (categoryId) REFERENCES sys_events_category (categoryId);
alter table sys_events add CONSTRAINT FK_sys_events_3 FOREIGN KEY (userId) REFERENCES sys_users (userId);

alter table sys_pages_relations add CONSTRAINT FK_sys_pages_relations_1 FOREIGN KEY (pageId) REFERENCES sys_pages (pageId);
alter table sys_pages_relations add CONSTRAINT FK_sys_pages_relations_2 FOREIGN KEY (pageIdRelative) REFERENCES sys_pages (pageId);