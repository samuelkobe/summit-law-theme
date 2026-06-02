/**
 * Mediation Intake Form
 *
 * Injects repeatable party fields and file uploads into Amelia's
 * Information step. On booking completion, submits intake data
 * to a custom AJAX endpoint that links it to the Amelia booking.
 */

export default class MediationIntake {
  constructor() {
    this.state = {
      plaintiffs: [{ name: "", counsel_name: "", counsel_email: "", assistant_name: "", assistant_email: "" }],
      defendants: [{ name: "", counsel_name: "", counsel_email: "", assistant_name: "", assistant_email: "" }],
      thirdParties: [],
      titleOfProceedingsId: null,
      thirdPartyClaimIds: [],
      teamMemberId: null,
    };

    this.injectedContainers = new WeakSet();
    this.submitted = false;
    this.init();
  }

  init() {
    // Use a single body-level observer to catch .am-fs__info-form and
    // .am-fs__congrats appearing anywhere — handles Amelia containers that
    // load late, get moved between staging/modal, or are rendered by
    // Gutenberg blocks after DOMContentLoaded.
    this.observer = new MutationObserver((mutations) => {
      // Check for info form (injection target)
      const infoForms = document.querySelectorAll(".am-fs__info-form");
      infoForms.forEach((infoForm) => {
        if (!this.injectedContainers.has(infoForm)) {
          this.injectedContainers.add(infoForm);
          requestAnimationFrame(() => {
            const ameliaContainer = infoForm.closest(
              '[id^="amelia-v2-booking"]',
            );
            this.detectTeamMember(ameliaContainer || infoForm);
            this.injectIntakeUI(infoForm);
          });
        }
      });

      // Check for congratulations screen (submission trigger)
      if (!this.submitted) {
        const congrats = document.querySelector(".am-fs__congrats");
        if (congrats) {
          this.submitted = true;
          this.submitIntake();
        }
      }
    });

    this.observer.observe(document.body, { childList: true, subtree: true });
  }

  /**
   * Detect the team member from the closest wrapper with data-team-id,
   * or from Amelia's employee name element.
   */
  detectTeamMember(container) {
    // Check for data-team-id on the container or its ancestors
    const wrapper = container.closest("[data-team-id]");
    if (wrapper) {
      this.state.teamMemberId = parseInt(wrapper.dataset.teamId, 10);
      return;
    }

    // Check for data-team-id on the modal that contains this Amelia form
    const modal = container.closest(".team-booking-modal");
    if (modal) {
      this.state.teamMemberId = parseInt(modal.dataset.teamId || "0", 10);
      if (this.state.teamMemberId) return;
    }

    // Check for the staging container that was moved into the modal
    const staging = container.closest('[id^="booking-staging-"]');
    if (staging) {
      const id = staging.id.replace("booking-staging-", "");
      this.state.teamMemberId = parseInt(id, 10);
      if (this.state.teamMemberId) return;
    }

    // Fallback: read the employee name from Amelia's DOM and pass it
    // to the server for matching against team member post titles
    const employeeNameEl =
      document.querySelector(".am-fs-iipu__employee-display-name") ||
      document.querySelector(".am-fcis__info-employee__name");
    if (employeeNameEl) {
      this.state.teamMemberName = employeeNameEl.textContent.trim();
    }
  }

  // ---------------------------------------------------------------------------
  // UI Injection
  // ---------------------------------------------------------------------------

  /**
   * Build and inject the intake form into Amelia's info form.
   */
  injectIntakeUI(infoForm) {
    const wrapper = document.createElement("div");
    wrapper.className = "mediation-intake-fields";
    wrapper.innerHTML = this.buildHTML();
    infoForm.appendChild(wrapper);

    this.bindRepeaters(wrapper);
    this.bindFileUploads(wrapper);
    this.syncStateFromInputs(wrapper);
    this.bindEmailValidation(wrapper);

    // Store a reference to Amelia's Continue button so we can toggle disabled
    const ameliaRoot =
      infoForm.closest('[id^="amelia-v2-booking"]') ||
      infoForm.closest("#amelia-container") ||
      document.body;
    this.continueBtn =
      ameliaRoot.querySelector(".am-button-continue") || null;
  }

  /**
   * Build the full intake HTML.
   */
  buildHTML() {
    return `
      <h4 class="mediation-intake-fields__heading">Mediation Case Information</h4>
      <p class="mediation-intake-fields__subtext">Please provide the parties involved in this mediation. If you are a party or counsel, please re-enter your details below so we have a complete case record.</p>

      ${this.buildPartyGroup("plaintiff", "Plaintiff", this.state.plaintiffs)}
      ${this.buildPartyGroup("defendant", "Defendant", this.state.defendants)}

      <div class="intake-section-divider"></div>

      <fieldset class="intake-file-group" data-file-type="titleOfProceedings">
        <legend>Title of Proceedings</legend>
        <p class="mediation-intake-fields__subtext" style="margin-bottom: 0.25rem;">All plaintiffs and defendants should be named in this document.</p>
        <div class="intake-file-input-wrapper">
          <input type="file" accept=".pdf,.doc,.docx" data-upload="titleOfProceedings">
        </div>
        <div class="intake-file-preview" data-preview="titleOfProceedings"></div>
      </fieldset>

      <div class="intake-section-divider"></div>

      ${this.buildPartyGroup("thirdParty", "Third Party", this.state.thirdParties)}
    `;
  }

  /**
   * Build a repeatable party group (plaintiff, defendant, or third party).
   */
  buildPartyGroup(type, label, entries) {
    const isOptional = type === "thirdParty";
    const rows = entries
      .map((entry, index) =>
        this.buildPartyRow(type, index, entry, isOptional || index > 0),
      )
      .join("");

    const optionalTag = isOptional
      ? ' <span class="intake-optional-tag">(Optional)</span>'
      : "";

    return `
      <fieldset class="intake-party-group" data-party-type="${type}">
        <legend>${label}(s)${optionalTag}</legend>
        <div class="intake-party-entries" data-entries="${type}">
          ${rows}
        </div>
        <button type="button" class="intake-add-entry" data-add="${type}">+ Add ${label}</button>
      </fieldset>
    `;
  }

  /**
   * Build a single party entry row.
   */
  buildPartyRow(type, index, data = {}, showRemove = false) {
    const label = this.labelFor(type);
    const entryHTML = `
      <div class="intake-party-entry" data-index="${index}">
        <span class="intake-party-entry__label">${label} ${index + 1}</span>
        <input type="text" placeholder="Name" data-field="name" value="${this.esc(data.name || "")}">
        <input type="text" placeholder="Counsel Name" data-field="counsel_name" value="${this.esc(data.counsel_name || "")}">
        <input type="email" placeholder="Counsel Email" data-field="counsel_email" value="${this.esc(data.counsel_email || "")}">
        <input type="text" placeholder="Legal Assistant / Law Clerk Name (optional)" data-field="assistant_name" value="${this.esc(data.assistant_name || "")}">
        <input type="email" placeholder="Legal Assistant / Law Clerk Email (optional)" data-field="assistant_email" value="${this.esc(data.assistant_email || "")}">
        ${showRemove ? `<button type="button" class="intake-remove-entry" aria-label="Remove ${label}">&times;</button>` : ""}
      </div>
    `;

    if (type !== "thirdParty") return entryHTML;

    return `
      <div class="intake-tp-block" data-tp-index="${index}">
        ${entryHTML}
        <div class="intake-claim-inline" data-claim-index="${index}">
          <label>Statement of Claim</label>
          <div class="intake-file-input-wrapper">
            <input type="file" accept=".pdf,.doc,.docx" data-upload="claim" data-claim-index="${index}">
          </div>
          <div class="intake-file-preview" data-preview="claim-${index}"></div>
        </div>
      </div>
    `;
  }

  /**
   * Get display label for a party type.
   */
  labelFor(type) {
    const labels = {
      plaintiff: "Plaintiff",
      defendant: "Defendant",
      thirdParty: "Third Party",
    };
    return labels[type] || type;
  }

  // ---------------------------------------------------------------------------
  // Event Binding
  // ---------------------------------------------------------------------------

  /**
   * Bind add/remove repeater buttons.
   */
  bindRepeaters(wrapper) {
    // Add buttons
    wrapper.querySelectorAll(".intake-add-entry").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        const type = btn.dataset.add;
        this.addPartyRow(wrapper, type);
      });
    });

    // Remove buttons (delegated)
    wrapper.addEventListener("click", (e) => {
      const removeBtn = e.target.closest(".intake-remove-entry");
      if (removeBtn) {
        e.preventDefault();
        const entry = removeBtn.closest(".intake-party-entry");
        const group = entry.closest(".intake-party-group");
        const type = group.dataset.partyType;
        // For third parties, remove the entire block (entry + inline upload)
        const toRemove = entry.closest(".intake-tp-block") || entry;
        toRemove.remove();
        this.reindexEntries(wrapper, type);
        this.updateStateFromDOM(wrapper);

        if (type === "thirdParty") {
          this.state.thirdPartyClaimIds = this.state.thirdPartyClaimIds.slice(
            0,
            this.state.thirdParties.length,
          );
        }
      }
    });
  }

  /**
   * Add a new party row.
   */
  addPartyRow(wrapper, type) {
    const stateKey = this.stateKeyFor(type);
    const newEntry = { name: "", counsel_name: "", counsel_email: "", assistant_name: "", assistant_email: "" };
    this.state[stateKey].push(newEntry);
    const index = this.state[stateKey].length - 1;

    const entriesContainer = wrapper.querySelector(`[data-entries="${type}"]`);
    const rowHTML = this.buildPartyRow(type, index, newEntry, true);
    entriesContainer.insertAdjacentHTML("beforeend", rowHTML);

    // The new element is a .intake-tp-block wrapper (third party) or .intake-party-entry
    const newEl = entriesContainer.lastElementChild;
    const partyEntry =
      newEl.querySelector(".intake-party-entry") || newEl;
    this.bindInputListeners(partyEntry, wrapper);

    // Bind file upload on new third party rows
    if (type === "thirdParty") {
      this.bindFileUploads(wrapper);
    }

    // Focus the first input in the new row
    const firstInput = newEl.querySelector('input[type="text"]');
    if (firstInput) firstInput.focus();
  }

  /**
   * Reindex entry data-index attributes after removal.
   */
  reindexEntries(wrapper, type) {
    const entries = wrapper.querySelectorAll(
      `[data-entries="${type}"] .intake-party-entry`,
    );
    const label = this.labelFor(type);
    const isOptional = type === "thirdParty";
    entries.forEach((entry, i) => {
      entry.dataset.index = i;
      // Update numbered label
      const entryLabel = entry.querySelector(".intake-party-entry__label");
      if (entryLabel) entryLabel.textContent = `${label} ${i + 1}`;
      // Show remove button only on rows after the first (all rows for optional groups)
      const removeBtn = entry.querySelector(".intake-remove-entry");
      if (i === 0 && removeBtn && !isOptional) {
        removeBtn.remove();
      }
    });

    // Re-index third party blocks and their inline claim uploads
    if (type === "thirdParty") {
      const blocks = wrapper.querySelectorAll(
        `[data-entries="thirdParty"] .intake-tp-block`,
      );
      blocks.forEach((block, i) => {
        block.dataset.tpIndex = i;
        const claim = block.querySelector(".intake-claim-inline");
        if (claim) {
          claim.dataset.claimIndex = i;
          const fileInput = claim.querySelector('input[type="file"]');
          if (fileInput) fileInput.dataset.claimIndex = i;
          const preview = claim.querySelector(".intake-file-preview");
          if (preview) preview.dataset.preview = `claim-${i}`;
        }
      });
    }
  }

  /**
   * Bind file upload change handlers.
   */
  bindFileUploads(wrapper) {
    wrapper
      .querySelectorAll('input[type="file"][data-upload]')
      .forEach((input) => {
        // Avoid double-binding
        if (input._intakeBound) return;
        input._intakeBound = true;

        input.addEventListener("change", () => {
          if (!input.files.length) return;
          this.uploadFile(input, wrapper);
        });
      });
  }

  /**
   * Bind input change listeners to sync state.
   */
  bindInputListeners(row, wrapper) {
    row.querySelectorAll("input[data-field]").forEach((input) => {
      input.addEventListener("input", () => {
        this.updateStateFromDOM(wrapper);
      });
    });
  }

  /**
   * Sync input listeners on initial injection.
   */
  syncStateFromInputs(wrapper) {
    wrapper
      .querySelectorAll(".intake-party-entry input[data-field]")
      .forEach((input) => {
        input.addEventListener("input", () => {
          this.updateStateFromDOM(wrapper);
        });
      });
  }

  /**
   * Validate counsel email fields on blur/input and sync the Continue button state.
   * Uses event delegation so dynamically added rows are covered.
   */
  bindEmailValidation(wrapper) {
    // Show error when leaving an invalid field, then sync button
    wrapper.addEventListener("focusout", (e) => {
      if (!e.target.matches('[data-field="counsel_email"], [data-field="assistant_email"]')) return;
      const val = e.target.value.trim();
      if (val && !this.isValidEmail(val)) {
        this.showEmailError(e.target);
      } else {
        this.clearEmailError(e.target);
      }
      this.updateContinueButton(wrapper);
    });

    // Clear error immediately as the user types a valid (or empty) value, then sync button
    wrapper.addEventListener("input", (e) => {
      if (!e.target.matches('[data-field="counsel_email"], [data-field="assistant_email"]')) return;
      const val = e.target.value.trim();
      if (!val || this.isValidEmail(val)) {
        this.clearEmailError(e.target);
      }
      this.updateContinueButton(wrapper);
    });
  }

  /**
   * Enable or disable Amelia's Continue button based on whether any counsel
   * email field contains a non-empty invalid value.
   *
   * Mirrors exactly what Amelia itself does: toggles the `disabled` attribute
   * and the `is-disabled` class. A native disabled button receives no click
   * events, so no event interception is needed.
   */
  updateContinueButton(wrapper) {
    if (!this.continueBtn) return;

    const hasInvalidEmail = Array.from(
      wrapper.querySelectorAll('[data-field="counsel_email"], [data-field="assistant_email"]'),
    ).some((input) => {
      const val = input.value.trim();
      return val && !this.isValidEmail(val);
    });

    if (hasInvalidEmail) {
      this.continueBtn.disabled = true;
      this.continueBtn.classList.add("is-disabled");
      this._intakeDisabledBtn = true;
    } else if (this._intakeDisabledBtn) {
      // Only re-enable if WE disabled it — don't override Amelia's own disabled state
      this.continueBtn.disabled = false;
      this.continueBtn.classList.remove("is-disabled");
      this._intakeDisabledBtn = false;
    }
  }

  /**
   * Read all party data from the DOM into state.
   */
  updateStateFromDOM(wrapper) {
    ["plaintiff", "defendant", "thirdParty"].forEach((type) => {
      const stateKey = this.stateKeyFor(type);
      const entries = wrapper.querySelectorAll(
        `[data-entries="${type}"] .intake-party-entry`,
      );
      this.state[stateKey] = Array.from(entries).map((entry) => ({
        name: entry.querySelector('[data-field="name"]')?.value || "",
        counsel_name:
          entry.querySelector('[data-field="counsel_name"]')?.value || "",
        counsel_email:
          entry.querySelector('[data-field="counsel_email"]')?.value || "",
        assistant_name:
          entry.querySelector('[data-field="assistant_name"]')?.value || "",
        assistant_email:
          entry.querySelector('[data-field="assistant_email"]')?.value || "",
      }));
    });
  }

  // ---------------------------------------------------------------------------
  // File Uploads
  // ---------------------------------------------------------------------------

  /**
   * Upload a file via AJAX and store the attachment ID.
   */
  async uploadFile(input, wrapper) {
    const file = input.files[0];
    const uploadType = input.dataset.upload; // 'titleOfProceedings' or 'claim'
    const claimIndex = parseInt(input.dataset.claimIndex || "0", 10);

    const previewKey =
      uploadType === "claim" ? `claim-${claimIndex}` : uploadType;
    const previewEl = wrapper.querySelector(`[data-preview="${previewKey}"]`);

    // Show uploading state
    const inputWrapper = input.closest(".intake-file-input-wrapper");
    if (inputWrapper) inputWrapper.classList.add("intake-file-uploading");
    if (previewEl)
      previewEl.innerHTML = `<span class="intake-file-spinner"></span> Uploading...`;

    const formData = new FormData();
    formData.append("action", "summit_intake_upload_file");
    formData.append("nonce", window.summitAjax?.nonce || "");
    formData.append("file", file);

    try {
      const response = await fetch(
        window.summitAjax?.ajaxurl || "/wp-admin/admin-ajax.php",
        {
          method: "POST",
          body: formData,
        },
      );

      const result = await response.json();

      if (result.success) {
        const { attachment_id, filename } = result.data;

        // Store in state
        if (uploadType === "titleOfProceedings") {
          this.state.titleOfProceedingsId = attachment_id;
        } else if (uploadType === "claim") {
          this.state.thirdPartyClaimIds[claimIndex] = attachment_id;
        }

        // Show preview
        if (previewEl) {
          previewEl.innerHTML = `
            <span class="filename">${this.esc(filename)}</span>
            <button type="button" class="remove-file" data-remove-upload="${uploadType}" data-claim-index="${claimIndex}">Remove</button>
          `;

          // Bind remove handler
          previewEl
            .querySelector(".remove-file")
            .addEventListener("click", () => {
              if (uploadType === "titleOfProceedings") {
                this.state.titleOfProceedingsId = null;
              } else if (uploadType === "claim") {
                this.state.thirdPartyClaimIds[claimIndex] = null;
              }
              previewEl.innerHTML = "";
              input.value = "";
            });
        }
      } else {
        if (previewEl) {
          previewEl.innerHTML = `<span class="intake-error-message">${result.data?.message || "Upload failed."}</span>`;
        }
      }
    } catch (err) {
      if (previewEl) {
        previewEl.innerHTML = `<span class="intake-error-message">Upload failed. Please try again.</span>`;
      }
    } finally {
      if (inputWrapper) inputWrapper.classList.remove("intake-file-uploading");
    }
  }

  // ---------------------------------------------------------------------------
  // Submission
  // ---------------------------------------------------------------------------

  /**
   * Submit all intake data to the server after Amelia booking completes.
   */
  async submitIntake() {
    // Read final state from DOM if the form is still attached
    const wrapper = document.querySelector(".mediation-intake-fields");
    if (wrapper) {
      this.updateStateFromDOM(wrapper);
    }

    // Validate counsel emails from state — runs even if the form has been
    // removed from the DOM by Amelia's step navigation
    let hasEmailError = false;
    for (const p of [
      ...this.state.plaintiffs,
      ...this.state.defendants,
      ...this.state.thirdParties,
    ]) {
      for (const field of ["counsel_email", "assistant_email"]) {
        const email = (p[field] || "").trim();
        if (email && !this.isValidEmail(email)) {
          hasEmailError = true;
          break;
        }
      }
      if (hasEmailError) break;
    }

    if (hasEmailError) {
      if (wrapper) {
        // Form is still in the DOM — show inline errors and retry button
        wrapper.querySelectorAll('[data-field="counsel_email"], [data-field="assistant_email"]').forEach((input) => {
          const val = input.value.trim();
          if (val && !this.isValidEmail(val)) {
            this.showEmailError(input);
          } else {
            this.clearEmailError(input);
          }
        });
        this.showRetryUI(wrapper);
      } else {
        // Amelia has already navigated away — show error near the congrats screen
        this.showCongratsError();
      }
      return;
    }

    if (wrapper) {
      this.clearRetryUI(wrapper);
      wrapper.classList.add("intake-submitting");
    }

    // Read employee name from the congrats screen — Amelia renders it as
    // <div class="am-fs__congrats-info-app-employee"><span>Attorney:</span><span>Name</span></div>
    if (!this.state.teamMemberId && !this.state.teamMemberName) {
      const employeeDiv = document.querySelector(
        ".am-fs__congrats-info-app-employee",
      );
      if (employeeDiv) {
        // Get the full text, then strip the label prefix (e.g. "Attorney:")
        let fullText = employeeDiv.textContent.trim();
        // Remove everything up to and including the first colon
        const colonIdx = fullText.indexOf(":");
        if (colonIdx !== -1) {
          fullText = fullText.substring(colonIdx + 1).trim();
        }
        // Also strip any trailing role/label words that might be appended
        // e.g. "Debbie Orth Attorney" -> "Debbie Orth"
        const knownSuffixes = ["Attorney", "Mediator", "Lawyer", "Counsel"];
        for (const suffix of knownSuffixes) {
          if (fullText.endsWith(" " + suffix)) {
            fullText = fullText.slice(0, -(suffix.length + 1)).trim();
            break;
          }
        }
        if (fullText) {
          this.state.teamMemberName = fullText;
        }
      }
    }

    const payload = {
      plaintiffs: this.state.plaintiffs,
      defendants: this.state.defendants,
      thirdParties: this.state.thirdParties,
      teamMemberId: this.state.teamMemberId,
      teamMemberName: this.state.teamMemberName || "",
      titleOfProceedingsId: this.state.titleOfProceedingsId,
      thirdPartyClaimIds: this.state.thirdPartyClaimIds.filter(Boolean),
    };

    // Use URL params for action + nonce so WordPress can verify them,
    // and send the intake data as JSON body
    const ajaxUrl = window.summitAjax?.ajaxurl || "/wp-admin/admin-ajax.php";
    const nonce = window.summitAjax?.nonce || "";
    const url = `${ajaxUrl}?action=summit_intake_submit&nonce=${encodeURIComponent(nonce)}`;

    try {
      const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const result = await response.json();

      if (!result.success) {
        console.error(
          "Mediation intake submission failed:",
          result.data?.message,
        );
      }
    } catch (err) {
      console.error("Mediation intake submission error:", err);
    }
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /**
   * Map party type to state key.
   */
  stateKeyFor(type) {
    const map = {
      plaintiff: "plaintiffs",
      defendant: "defendants",
      thirdParty: "thirdParties",
    };
    return map[type] || type;
  }

  /**
   * Validate an email address using the browser's built-in parser.
   */
  isValidEmail(val) {
    const input = document.createElement("input");
    input.type = "email";
    input.value = val;
    return input.checkValidity();
  }

  /**
   * Mark a counsel email input as invalid and insert an error message after it.
   */
  showEmailError(input) {
    input.classList.add("intake-field-error");
    if (!input.nextElementSibling?.classList.contains("intake-email-error")) {
      const msg = document.createElement("span");
      msg.className = "intake-error-message intake-email-error";
      msg.textContent = "Please enter a valid email address.";
      input.insertAdjacentElement("afterend", msg);
    }
  }

  /**
   * Remove the invalid state and error message from a counsel email input.
   */
  clearEmailError(input) {
    input.classList.remove("intake-field-error");
    const msg = input.nextElementSibling;
    if (msg?.classList.contains("intake-email-error")) {
      msg.remove();
    }
  }

  /**
   * Show a banner with a retry button when submission is blocked by email errors.
   */
  showRetryUI(wrapper) {
    if (wrapper.querySelector(".intake-retry-banner")) return;
    const banner = document.createElement("div");
    banner.className = "intake-retry-banner";
    banner.innerHTML = `
      <span class="intake-error-message">Some email addresses are invalid — please correct them above and click <strong>Submit Intake</strong>.</span>
      <button type="button" class="intake-retry-btn">Submit Intake</button>
    `;
    banner.querySelector(".intake-retry-btn").addEventListener("click", () => {
      this.submitIntake();
    });
    wrapper.appendChild(banner);
  }

  /**
   * Remove the retry banner once submission succeeds.
   */
  clearRetryUI(wrapper) {
    wrapper.querySelector(".intake-retry-banner")?.remove();
  }

  /**
   * Show an error message on the Amelia congrats screen when validation fails
   * after the intake form has already been removed from the DOM.
   */
  showCongratsError() {
    const congrats = document.querySelector(".am-fs__congrats");
    if (!congrats) return;
    if (congrats.querySelector(".intake-congrats-error")) return;
    const banner = document.createElement("div");
    banner.className = "intake-congrats-error";
    banner.style.cssText =
      "margin:0 0 16px;padding:14px 16px;background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;color:#991b1b;font-size:14px;line-height:1.5;";
    banner.innerHTML =
      "<strong>Intake not submitted — invalid email address.</strong><br>" +
      "One or more counsel email addresses were invalid and could not be saved. " +
      "Please contact us to provide the correct email address(es) so we can complete your intake record.";
    congrats.insertAdjacentElement("afterbegin", banner);
  }

  /**
   * Escape HTML for safe insertion.
   */
  esc(str) {
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }
}
