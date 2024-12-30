create table variables
(
    name      varchar(255)                           not null comment 'The unique index for the variable name'
        primary key,
    value     text                                   null comment 'The value of the variable',
    read_only tinyint(1) default 0                   not null comment 'Boolean indicator if the variable is read only',
    created   timestamp  default current_timestamp() not null comment 'The Timestamp for when this record was created',
    updated   timestamp                              null comment 'The Timestamp for when this record was last updated',
    constraint variables_name_uindex
        unique (name) comment 'The unique index for the variable name'
);

