import type { INestApplication } from '@nestjs/common';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import type { Request, Response } from 'express';

export function setupSwagger(app: INestApplication): void {
  const config = new DocumentBuilder()
    .setTitle('Revue de presse — HTTP API')
    .setVersion('v6.0.0')
    .setDescription('Daily curated highlights from French press Bluesky accounts.')
    .setContact('revue-de-presse', '', 'contact@revue-de-presse.org')
    .setLicense('GPL-3.0-or-later', '')
    .addBearerAuth()
    .build();

  const document = SwaggerModule.createDocument(app, config);
  SwaggerModule.setup('api/docs', app, document, { useGlobalPrefix: false });

  const http = app.getHttpAdapter();
  http.get('/api/docs.json', (_req: Request, res: Response) => {
    res.setHeader('Content-Type', 'application/json');
    res.json(document);
  });
  http.get('/api/docs.jsonld', (_req: Request, res: Response) => {
    res.setHeader('Content-Type', 'application/ld+json');
    res.json({
      '@context': '/api/contexts/Documentation',
      '@id': '/api/docs.jsonld',
      '@type': 'Documentation',
      openapi: document,
    });
  });
}
