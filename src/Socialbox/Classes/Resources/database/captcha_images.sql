create table captcha_images
(
    uuid      varchar(36)                 default uuid()              not null comment 'The Unique Universal Identifier of the captcha record'
        primary key comment 'The Unique index for the UUID column',
    peer_uuid varchar(36)                                             not null comment 'The UUID of the peer that is associated with this captcha challenge',
    status    enum ('UNSOLVED', 'SOLVED') default 'UNSOLVED'          not null comment 'The status of the current captcha',
    answer    varchar(8)                                              null comment 'The current answer for the captcha',
    answered  timestamp                                               null comment 'The Timestamp for when this captcha was answered',
    created   timestamp                   default current_timestamp() not null comment 'The Timestamp for when this captcha record was created',
    constraint captchas_peer_uuid_uindex
        unique (peer_uuid) comment 'The Primary Unique Index for the peer UUID',
    constraint captchas_registered_peers_uuid_fk
        foreign key (peer_uuid) references peers (uuid)
            on update cascade on delete cascade
);

create index captchas_status_index
    on captcha_images (status)
    comment 'The Index for the captcha status';

create index captchas_uuid_index
    on captcha_images (uuid)
    comment 'The Unique index for the UUID column';

