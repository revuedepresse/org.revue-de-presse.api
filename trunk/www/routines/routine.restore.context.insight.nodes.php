<?php

$class_db = $class_application::getDbClass();

$restore_insight_nodes = '
    TRUNCATE weaving_insight_node;
    TRUNCATE weaving_insight;
    TRUNCATE weaving_arc;
    TRUNCATE weaving_edge;

    INSERT INTO weaving_insight_node (isn_id, isg_id, isn_status, isn_type, isn_parent, isn_body, isn_date_creation, isn_date_modification) VALUES
    (1, 1, 0, 0, 0, "test", "2010-08-15 17:43:40", "0000-00-00 00:00:00"),
    (2, 2, 1, 0, 0, "marche ?", "2010-08-15 17:46:30", "0000-00-00 00:00:00"),
    (3, 2, 1, 0, 0, "sûr ?", "2010-08-15 17:47:01", "2010-09-07 22:31:39"),
    (4, 2, 1, 0, 2, "sûr pour voir ?", "2010-08-15 17:48:17", "2010-09-11 20:25:37"),
    (5, 2, 1, 0, 4, "Test encore", "2010-08-15 22:23:22", "2010-09-11 20:25:37"),
    (6, 2, 1, 0, 4, "Nouveau test", "2010-08-20 19:42:01", "2010-09-11 20:25:37"),
    (7, 2, 1, 0, 5, "dernier test pour la suppression ?", "2010-08-20 22:23:52", "2010-09-11 20:25:59"),
    (8, 2, 1, 0, 6, "what ?", "2010-08-20 22:24:10", "2010-09-11 20:25:59"),
    (9, 2, 1, 0, 0, "On va faire un nouveau test ;)", "2010-09-12 16:04:19", "2010-09-12 16:05:35");

    # ([0-9], [0-9], [0-9]), 0,
    # \1, 51

    INSERT INTO weaving_insight (isg_id, isg_status, ety_id, isg_target, isg_date_creation, isg_date_modification) VALUES
    (1, 1, 0, 86, "2010-08-15 17:43:39", "0000-00-00 00:00:00"),
    (2, 1, 0, 89, "2010-08-15 17:46:30", "0000-00-00 00:00:00");
    
    INSERT INTO weaving_arc (arc_id, arc_status, arc_type, arc_source, arc_destination) VALUES
    (1, 1, 1, 1, 6),
    (2, 1, 1, 2, 7),
    (3, 1, 1, 1, 8),
    (4, 1, 1, 1, 9),
    (5, 1, 1, 1, 10),
    (6, 1, 1, 1, 11),
    (7, 1, 1, 1, 12),
    (8, 1, 1, 1, 13),
    (9, 1, 1, 2, 14),
    (10, 1, 1, 1, 15),
    (11, 1, 1, 1, 16);
    
    INSERT INTO weaving_edge (edg_id, ety_id, edg_status, edg_key) VALUES
    (1, 74, 1, 1),
    (2, 74, 1, 3),
    (3, 74, 1, 4),
    (4, 74, 1, 5),
    (5, 74, 1, 6),
    (6, 35, 1, 1),
    (7, 35, 1, 2),
    (8, 35, 1, 3),
    (9, 35, 1, 4),
    (10, 35, 1, 5),
    (11, 35, 1, 6),
    (12, 35, 1, 7),
    (13, 35, 1, 8),
    (14, 35, 1, 9),
    (15, 34, 1, 1),
    (16, 34, 1, 2);
    
    UPDATE weaving_preprod.weaving_insight SET ety_id = 47 WHERE weaving_insight.isg_id =1 LIMIT 1 ;
    
    UPDATE weaving_preprod.weaving_insight SET ety_id = 47 WHERE weaving_insight.isg_id =2 LIMIT 1 ;
    
    UPDATE weaving_preprod.weaving_insight SET ety_id = 47 WHERE weaving_insight.isg_id =3 LIMIT 1 ;
';

$class_db::multiQuery($restore_insight_nodes);
?>