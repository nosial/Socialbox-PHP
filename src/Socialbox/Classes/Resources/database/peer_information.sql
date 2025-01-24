create table peer_information
(
    peer_uuid      varchar(36)                                                                                                                            not null comment 'The Unique Universal Identifier for the peer',
    property_name  enum ('DISPLAY_NAME', 'DISPLAY_PICTURE', 'FIRST_NAME', 'MIDDLE_NAME', 'LAST_NAME', 'EMAIL_ADDRESS', 'PHONE_NUMBER', 'BIRTHDAY', 'URL') not null comment 'The name of the property',
    property_value varchar(256)                                                                                                                           not null comment 'The value of the property associated with the peer',
    privacy_state  enum ('PUBLIC', 'PRIVATE', 'CONTACTS', 'TRUSTED') default 'PRIVATE'                                                                    not null comment 'The privacy setting for the information property',
    primary key (property_name, peer_uuid),
    constraint peer_information_peer_uuid_property_name_uindex
        unique (peer_uuid, property_name) comment 'The Unique Index for the the peer uuid & property name combination',
    constraint peer_information_registered_peers_uuid_fk
        foreign key (peer_uuid) references peers (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing peer information';

create index peer_information_peer_uuid_index
    on peer_information (peer_uuid)
    comment 'The index for the peer uuid';

