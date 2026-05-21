import { SetMetadata } from '@nestjs/common';
export const IS_PUBLIC = 'auth:is_public';
export const Public = () => SetMetadata(IS_PUBLIC, true);
