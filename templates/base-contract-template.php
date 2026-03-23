{{CONTRACT_HTML}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{contract_number}} — Pylaris</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --orange: #FF6B35;
    --dark:   #0F0F0F;
    --text:   #1A1A1A;
    --muted:  #6B6860;
    --border: #E0DDD8;
    --bg:     #F2EFE9;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: #fff;
    color: var(--text);
    padding: 40px 32px;
  }

  /* Header del documento */
  .doc-header {
    background: var(--dark);
    padding: 36px 48px;
    border-radius: 4px 4px 0 0;
    position: relative;
    overflow: hidden;
    margin: -40px -32px 0;
  }

  .doc-header::before {
    content: '';
    position: absolute;
    top: -60px; right: -40px;
    width: 200px; height: 200px;
    background: var(--orange);
    opacity: .06;
    border-radius: 50%;
  }

  .doc-header__top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 28px;
  }

  .doc-brand {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -.5px;
  }

  .doc-brand span { color: var(--orange); }

  .doc-ref {
    font-size: 11px;
    color: rgba(255,255,255,.4);
    text-align: right;
    line-height: 1.8;
  }

  .doc-title {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    font-weight: 600;
    color: #fff;
    line-height: 1.3;
    margin-bottom: 4px;
  }

  .doc-subtitle {
    font-size: 12px;
    color: rgba(255,255,255,.45);
    letter-spacing: .5px;
    text-transform: uppercase;
  }

  /* Cuerpo */
  .doc-body {
    padding: 40px 0;
    max-width: 760px;
  }

  /* Grid de metadatos */
  .meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 36px;
    font-size: 14px;
  }

  .meta-cell {
    padding: 14px 18px;
    border-right: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
  }

  .meta-cell:nth-child(even)      { border-right: none; }
  .meta-cell:nth-last-child(-n+2) { border-bottom: none; }

  .meta-label {
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 3px;
  }

  .meta-value { font-size: 14px; font-weight: 500; }

  /* Secciones */
  .section { margin-bottom: 28px; }

  .section__header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
  }

  .section__num {
    width: 24px; height: 24px;
    background: var(--orange);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .section__title {
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
  }

  .section__body {
    padding-left: 34px;
  }

  p {
    font-size: 14px;
    line-height: 1.8;
    color: #3A3A3A;
    margin-bottom: 10px;
  }

  ul {
    padding-left: 20px;
    margin-bottom: 10px;
  }

  ul li {
    font-size: 14px;
    line-height: 1.8;
    color: #3A3A3A;
    margin-bottom: 3px;
  }

  .divider {
    height: 1px;
    background: var(--border);
    margin: 28px 0;
  }

  /* Firmas */
  .signatures {
    margin-top: 40px;
    padding-top: 28px;
    border-top: 2px solid var(--border);
  }

  .signatures__title {
    font-family: 'Playfair Display', serif;
    font-size: 15px;
    font-weight: 600;
    text-align: center;
    margin-bottom: 24px;
  }

  .signatures__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
  }

  .sig__line {
    border-top: 2px solid var(--dark);
    margin-bottom: 8px;
    margin-top: 56px;
  }

  .sig__name { font-size: 13px; font-weight: 500; }
  .sig__role { font-size: 12px; color: var(--muted); margin-top: 2px; }

  /* Footer */
  .doc-footer {
    margin: 0 -32px -40px;
    background: #FAFAF8;
    border-top: 1.5px solid var(--border);
    padding: 16px 48px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: var(--muted);
  }

  .doc-footer__brand {
    font-family: 'Playfair Display', serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--dark);
  }

  .doc-footer__brand span { color: var(--orange); }
</style>
</head>
<body>

<!-- HEADER -->
<div class="doc-header">
  <div class="doc-header__top">
    <div class="doc-brand">Pyla<span>ris</span></div>
    <div class="doc-ref">
      N° {{contract_number}}<br>
      Fecha: {{contract_date}}
    </div>
  </div>
  <div class="doc-title">Contrato de Diseño y Desarrollo Web</div>
  <div class="doc-subtitle">Agencia de Marketing Digital · Pylaris</div>
</div>

<!-- BODY -->
<div class="doc-body">

  <!-- Metadatos de partes -->
  <div class="meta-grid">
    <div class="meta-cell">
      <div class="meta-label">Prestador de servicios</div>
      <div class="meta-value">Pylaris — Agencia de Marketing Digital</div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">Representante</div>
      <div class="meta-value">Benjamin Jimenez<br><small style="color:#888;font-size:12px;font-weight:400;">CUIT 20-43242871-1</small></div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">Cliente</div>
      <div class="meta-value">{{client_name}}{{#client_company}}<br><small style="color:#888;font-size:12px;font-weight:400;">{{client_company}}</small>{{/client_company}}</div>
    </div>
    <div class="meta-cell">
      <div class="meta-label">CUIT / DNI</div>
      <div class="meta-value">{{client_dni_cuit}}</div>
    </div>
  </div>

  <!-- 1. Objeto -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">1</div>
      <div class="section__title">Objeto del Contrato</div>
    </div>
    <div class="section__body">
      <p>Pylaris se compromete a diseñar y desarrollar {{#project_title}}el proyecto "{{project_title}}"{{/project_title}}{{^project_title}}el sitio web solicitado{{/project_title}} para {{client_name}}, conforme a los requerimientos acordados entre las partes. El proyecto comprende:</p>
      <p>{{project_scope}}</p>
      <p>Cualquier funcionalidad adicional no incluida en el presente contrato deberá cotizarse por separado.</p>
    </div>
  </div>

  <div class="divider"></div>

  <!-- 2. Plazos -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">2</div>
      <div class="section__title">Plazos y Entregas</div>
    </div>
    <div class="section__body">
      <p>El plazo estimado de entrega es de <strong>{{delivery_time}}</strong> a partir de la fecha de pago del anticipo y recepción de todos los materiales (textos, imágenes, accesos, etc.) por parte del cliente.</p>
      <p>El proyecto contempla hasta <strong>{{revision_rounds}} rondas de revisión</strong> generales incluidas en el precio. Las revisiones adicionales o cambios de alcance se cotizarán por separado.</p>
      <p>El incumplimiento del cliente en la entrega de materiales en tiempo y forma podrá extender los plazos sin responsabilidad para Pylaris.</p>
    </div>
  </div>

  <div class="divider"></div>

  <!-- 3. Honorarios -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">3</div>
      <div class="section__title">Honorarios y Forma de Pago</div>
    </div>
    <div class="section__body">
      <p>El valor total del proyecto es de <strong>{{project_amount}} {{project_currency}}</strong>, abonado de la siguiente manera:</p>
      <ul>
        <li><strong>50% de anticipo:</strong> al momento de firmar este contrato.</li>
        <li><strong>50% restante:</strong> previo a la publicación del sitio en producción.</li>
      </ul>
      <p>En caso de mora, Pylaris podrá suspender el trabajo hasta regularizar la situación.</p>
    </div>
  </div>

  <div class="divider"></div>

  <!-- 4. Propiedad Intelectual -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">4</div>
      <div class="section__title">Propiedad Intelectual</div>
    </div>
    <div class="section__body">
      <p>Una vez abonado el monto total, los derechos sobre el diseño y los archivos entregables quedarán en poder del cliente. Pylaris conservará el derecho de mostrar el trabajo como parte de su portafolio, salvo solicitud expresa y por escrito del cliente.</p>
      <p>Los plugins, plantillas, frameworks y licencias de software utilizados conservan sus respectivas licencias originales. El cliente es responsable de renovar o mantener las licencias de terceros que pudieran corresponder.</p>
    </div>
  </div>

  <div class="divider"></div>

  <!-- 5. Obligaciones del Cliente -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">5</div>
      <div class="section__title">Obligaciones del Cliente</div>
    </div>
    <div class="section__body">
      <p>El cliente se compromete a:</p>
      <ul>
        <li>Proveer todos los materiales necesarios (textos, imágenes, logos, accesos) en los plazos acordados.</li>
        <li>Designar un interlocutor responsable para la toma de decisiones.</li>
        <li>Revisar y aprobar (o solicitar correcciones) en un plazo máximo de 5 días hábiles por entrega parcial.</li>
        <li>No ceder ni compartir con terceros los accesos al sistema de gestión del sitio sin autorización.</li>
      </ul>
    </div>
  </div>

  <div class="divider"></div>

  <!-- 6. Mantenimiento -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">6</div>
      <div class="section__title">Mantenimiento Post-Entrega</div>
    </div>
    <div class="section__body">
      <p>Pylaris brindará soporte técnico sin costo durante <strong>30 días calendario</strong> desde la fecha de publicación del sitio, limitado a corrección de errores propios del trabajo entregado.</p>
      <p>El mantenimiento continuo (actualizaciones, backups, nuevos contenidos, cambios de diseño) deberá contratarse como servicio adicional.</p>
    </div>
  </div>

  <div class="divider"></div>

  <!-- 7. Confidencialidad -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">7</div>
      <div class="section__title">Confidencialidad</div>
    </div>
    <div class="section__body">
      <p>Ambas partes se comprometen a mantener la confidencialidad de toda información sensible intercambiada durante la vigencia del presente contrato, y no divulgarla a terceros sin consentimiento previo y por escrito.</p>
    </div>
  </div>

  <div class="divider"></div>

  <!-- 8. Rescisión -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">8</div>
      <div class="section__title">Rescisión del Contrato</div>
    </div>
    <div class="section__body">
      <p>Cualquiera de las partes podrá rescindir este contrato con un preaviso mínimo de 15 días hábiles por escrito. En caso de rescisión por parte del cliente una vez iniciado el proyecto, el anticipo no será reembolsable. Pylaris facturará adicionalmente el trabajo ejecutado hasta la fecha de rescisión.</p>
    </div>
  </div>

  <div class="divider"></div>

  <!-- 9. Jurisdicción -->
  <div class="section">
    <div class="section__header">
      <div class="section__num">9</div>
      <div class="section__title">Jurisdicción</div>
    </div>
    <div class="section__body">
      <p>Para todos los efectos legales emergentes del presente contrato, las partes se someten a la jurisdicción de los tribunales ordinarios de <strong>{{jurisdiction}}</strong>, renunciando a cualquier otro fuero que pudiera corresponder.</p>
    </div>
  </div>

  <!-- Firmas -->
  <div class="signatures">
    <div class="signatures__title">Conformidad de las Partes</div>
    <div class="signatures__grid">
      <div>
        <div class="sig__line"></div>
        <div class="sig__name">Pylaris — Agencia de Marketing Digital</div>
        <div class="sig__role">Benjamin Jimenez · Director</div>
      </div>
      <div>
        <div class="sig__line"></div>
        <div class="sig__name">{{client_name}}</div>
        <div class="sig__role">{{client_dni_cuit}}</div>
      </div>
    </div>
  </div>

</div><!-- .doc-body -->

<!-- FOOTER -->
<div class="doc-footer">
  <span>pylaris.com · Documento privado y confidencial</span>
  <div class="doc-footer__brand">Pyla<span>ris</span></div>
</div>

</body>
</html>
