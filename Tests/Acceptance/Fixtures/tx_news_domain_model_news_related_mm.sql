-- `related` sets MM_opposite_field, so the record owning the field is stored in uid_foreign and the related records
-- in uid_local.
-- News 1 relates to news 2, 3 and 4.
-- News 11, 13, 15, 19, 21, 23, 33, 58 and 71 relate to news 60, for the group default of the default filters module.
INSERT INTO `tx_news_domain_model_news_related_mm` (`uid_local`, `uid_foreign`, `sorting`, `sorting_foreign`)
VALUES
(2, 1, 0, 1),
(3, 1, 0, 2),
(4, 1, 0, 3),
(60, 11, 0, 1),
(60, 13, 0, 1),
(60, 15, 0, 1),
(60, 19, 0, 1),
(60, 21, 0, 1),
(60, 23, 0, 1),
(60, 33, 0, 1),
(60, 58, 0, 1),
(60, 71, 0, 1);
