import { z } from 'zod';

/**
 * TikTok Events delivers webhooks with a small envelope (event identifier,
 * issuing client_key, create_time, target user, and an opaque content payload
 * whose shape varies per event). We only validate the envelope at the edge
 * and leave event-specific parsing to downstream handlers.
 */
export const TikTokWebhookEnvelopeSchema = z.object({
  event: z.string().min(1),
  client_key: z.string().min(1),
  create_time: z.number().int(),
  user_openid: z.string().optional(),
  content: z.unknown().optional(),
});

export type TikTokWebhookEnvelope = z.infer<typeof TikTokWebhookEnvelopeSchema>;
