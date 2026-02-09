/*
Template Name: Fabkin - Admin & Dashboard Template
Author: Pixeleyez
Website: https://pixeleyez.com/
Contact: pixeleyez@gmail.com
File: app js
*/

// Initialize Bootstrap tooltips and popovers
function initializeBootstrapComponents(selector, Component) {
    const triggerList = document.querySelectorAll(selector);
    return [...triggerList].map((triggerEl) => new Component(triggerEl));
}

// Initialize tooltips
const tooltips = initializeBootstrapComponents(
    '[data-bs-toggle="tooltip"]',
    bootstrap.Tooltip
);

// Initialize popovers
const popovers = initializeBootstrapComponents(
    '[data-bs-toggle="popover"]',
    bootstrap.Popover
);

// Function to handle both sticky menu and button loading
function initializeAppFeatures() {
    const stickyMenu = document.getElementById("appHeader"); // Ensure this ID matches your HTML
    const stickyOffset = stickyMenu.offsetTop;

    // Function to toggle sticky class on scroll
    function toggleStickyMenu() {
        if (window.scrollY > stickyOffset) {
            stickyMenu.classList.add("sticky-scroll");
        } else {
            stickyMenu.classList.remove("sticky-scroll");
        }
    }

    // Attach the scroll event listener
    window.addEventListener("scroll", toggleStickyMenu);

    // Attach click event listeners to all loader buttons
    document.querySelectorAll(".btn-loader").forEach((button) => {
        button.addEventListener("click", function () {
            const indicatorLabel = this.querySelector(".indicator-label");
            const originalText = indicatorLabel.textContent;
            const loadingText = this.getAttribute("data-loading-text");

            // Show loading indicator and disable button
            this.classList.add("loading");
            indicatorLabel.textContent = loadingText;
            this.disabled = true;

            // Simulate an asynchronous operation (e.g., form submission)
            setTimeout(() => {
                // Hide loading indicator and reset button
                this.classList.remove("loading");
                indicatorLabel.textContent = originalText;
                this.disabled = false;
            }, 1500); // Simulated delay of 1.5 seconds
        });
    });
}

function setOptions(selector, options) {
    selector.selectpicker('destroy');
    selector.empty();
    Object.entries(options).forEach(([key, value]) => {
        let text = value.name;
        let optionValue = value.id;

        if (value.name === undefined) {
            text = `${value.first_name} ${value.last_name}`;
            if (selector.hasClass('unit-select')) {
                text = value.unit;
            }
        }

        if (selector.attr('id') == 'role') {
            optionValue = value.name;
        }
        selector.append(`<option value="${optionValue}">${text}</option>`)
    })
    selector.selectpicker();
}

function triggerCleave(decimals = 2)
{
    for(let field of $('.cleave-input').toArray()){
        new Cleave(field, {
            numeral: true,
            numeralThousandsGroupStyle: 'thousand',
            numeralPositiveOnly: true,
            numeralDecimalScale: decimals,
        });
    }
}

function triggerCleavePhone()
{
    for(let field of $('.cleave-phone-input').toArray()){
        new Cleave(field, {
            phone: true,
            phoneRegionCode: 'LB'
        });
    }
}

// // Call the function to initialize features
// document.addEventListener("DOMContentLoaded", initializeAppFeatures);

// function initChoices()
// {
//     for(let choice of $('.default-choice').toArray()) {
//         new Choices(choice, {
//             searchEnabled: true,
//             itemSelectText: 'Press to select',
//         });
//     }
// }
// const choicesInstances = new Map();

// function destroyChoicesById(id) {
//     const el = document.getElementById(id);
//     if (!el) return;

//     const instance = choicesInstances.get(id);
//     if (instance && typeof instance.destroy === "function") {
//         try {
//             instance.destroy();
//         } catch (err) {
//             console.warn(`Error destroying Choices for #${id}:`, err);
//         }
//     }
//     choicesInstances.delete(id);

//     // Remove leftover wrapper if exists
//     const wrapper = el.closest('.choices');
//     if (wrapper && wrapper.parentNode) {
//         wrapper.parentNode.insertBefore(el, wrapper);
//         wrapper.remove();
//     }

//     // Reset element state
//     el.classList.remove('choices__input');
//     el.removeAttribute('data-choice');
    
//     // Clear all options (important for empty roles)
//     el.innerHTML = '';
// }


// function initChoicesById(id, config = {}) {
//     // Always destroy first to ensure clean init
//     destroyChoicesById(id);

//     const el = document.getElementById(id);
//     if (!el) return null;

//     const instance = new Choices(el, {
//         removeItemButton: true,
//         searchEnabled: true,
//         shouldSort: false,
//         ...config,
//     });

//     choicesInstances.set(id, instance);
//     return instance;
// }


