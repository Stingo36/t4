<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* themes/bootstrap5/templates/user/user.html.twig */
class __TwigTemplate_9e40b77fb430e82edf080d221ee3eba5 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<article";
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [0 => "profile"], "method", false, false, true, 1), 1, $this->source), "html", null, true);
        echo ">
    ";
        // line 2
        $context["fields"] = ["Personal Information - Updated CV" =>  !twig_test_empty((($__internal_compile_0 = twig_get_attribute($this->env, $this->source,         // line 3
($context["content"] ?? null), "field_user_basic_info_ref", [], "any", false, false, true, 3)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["#items"] ?? null) : null)), "Academic Qualifications" =>  !twig_test_empty((($__internal_compile_1 = twig_get_attribute($this->env, $this->source,         // line 4
($context["content"] ?? null), "field_user_academic_qualifi_ref", [], "any", false, false, true, 4)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["#items"] ?? null) : null)), "Other relevant information - PhD Details" =>  !twig_test_empty((($__internal_compile_2 = twig_get_attribute($this->env, $this->source,         // line 5
($context["content"] ?? null), "field_user_other_rel_info_ref", [], "any", false, false, true, 5)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["#items"] ?? null) : null)), "Upload Research Proposal" =>  !twig_test_empty((($__internal_compile_3 = twig_get_attribute($this->env, $this->source,         // line 6
($context["content"] ?? null), "field_user_research_proposal_ref", [], "any", false, false, true, 6)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["#items"] ?? null) : null)), "Upload Publications" =>  !twig_test_empty((($__internal_compile_4 = twig_get_attribute($this->env, $this->source,         // line 7
($context["content"] ?? null), "field_user_update_pub_ref", [], "any", false, false, true, 7)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["#items"] ?? null) : null)), "You must provide a minimum of 8 referees" =>  !twig_test_empty((($__internal_compile_5 = twig_get_attribute($this->env, $this->source,         // line 8
($context["content"] ?? null), "field_user_list_of_referees_ref", [], "any", false, false, true, 8)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["#items"] ?? null) : null))];
        // line 10
        echo "
    ";
        // line 11
        $context["not_submitted"] = [];
        // line 12
        echo "    ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["fields"] ?? null));
        foreach ($context['_seq'] as $context["label"] => $context["submitted"]) {
            // line 13
            echo "        ";
            if ( !$context["submitted"]) {
                // line 14
                echo "            ";
                $context["not_submitted"] = twig_array_merge($this->sandbox->ensureToStringAllowed(($context["not_submitted"] ?? null), 14, $this->source), [0 => $context["label"]]);
                // line 15
                echo "        ";
            }
            // line 16
            echo "    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['label'], $context['submitted'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 17
        echo "
";
        // line 18
        if (twig_get_attribute($this->env, $this->source, ($context["user"] ?? null), "hasRole", [0 => "user"], "method", false, false, true, 18)) {
            echo " 
        ";
            // line 19
            if (twig_test_empty((($__internal_compile_6 = twig_get_attribute($this->env, $this->source, ($context["content"] ?? null), "field_user_session_key", [], "any", false, false, true, 19)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6["#items"] ?? null) : null))) {
                // line 20
                echo "            ";
                if (($context["not_submitted"] ?? null)) {
                    // line 21
                    echo "                Use the links on the left to navigate between sections.<br />    
                Once you have filled in all the required fields, go to 'Submit Application' and click 'Submit'.<br />
                If you do not submit your application within 15 days from the time you register, it will be automatically deleted, and you will need to re-start the procedure.<br />
                You have <b>";
                    // line 24
                    echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["user_days_remaining"] ?? null), 24, $this->source), "html", null, true);
                    echo "</b> days remaining.<br />
                <br />
                <p>The following fields are still incomplete:<br />
                ";
                    // line 27
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(($context["not_submitted"] ?? null));
                    $context['loop'] = [
                      'parent' => $context['_parent'],
                      'index0' => 0,
                      'index'  => 1,
                      'first'  => true,
                    ];
                    if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                        $length = count($context['_seq']);
                        $context['loop']['revindex0'] = $length - 1;
                        $context['loop']['revindex'] = $length;
                        $context['loop']['length'] = $length;
                        $context['loop']['last'] = 1 === $length;
                    }
                    foreach ($context['_seq'] as $context["_key"] => $context["label"]) {
                        // line 28
                        echo "                    <b><span class=\"text-danger\">";
                        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 28), 28, $this->source), "html", null, true);
                        echo ". ";
                        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($context["label"], 28, $this->source), "html", null, true);
                        echo "</span></b><br />
                ";
                        ++$context['loop']['index0'];
                        ++$context['loop']['index'];
                        $context['loop']['first'] = false;
                        if (isset($context['loop']['length'])) {
                            --$context['loop']['revindex0'];
                            --$context['loop']['revindex'];
                            $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                        }
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['label'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 30
                    echo "            ";
                } else {
                    // line 31
                    echo "                Use the links on the left to navigate between sections.<br />    
                Once you have filled in all the required fields, go to 'Submit Application' and click 'Submit'.<br />
                If you do not submit your application within 15 days from the time you register, it will be automatically deleted, and you will need to re-start the procedure.<br />
                You have <b>";
                    // line 34
                    echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["user_days_remaining"] ?? null), 34, $this->source), "html", null, true);
                    echo "</b> days remaining.<br />
            ";
                }
                // line 36
                echo "        ";
            } else {
                // line 37
                echo "            All sections are submitted.
        ";
            }
            // line 39
            echo "    ";
        } else {
            // line 40
            echo "        ";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["content"] ?? null), 40, $this->source), "html", null, true);
            echo "
    ";
        }
        // line 42
        echo "</article>
";
    }

    public function getTemplateName()
    {
        return "themes/bootstrap5/templates/user/user.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  165 => 42,  159 => 40,  156 => 39,  152 => 37,  149 => 36,  144 => 34,  139 => 31,  136 => 30,  117 => 28,  100 => 27,  94 => 24,  89 => 21,  86 => 20,  84 => 19,  80 => 18,  77 => 17,  71 => 16,  68 => 15,  65 => 14,  62 => 13,  57 => 12,  55 => 11,  52 => 10,  50 => 8,  49 => 7,  48 => 6,  47 => 5,  46 => 4,  45 => 3,  44 => 2,  39 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "themes/bootstrap5/templates/user/user.html.twig", "/var/www/html/ASM/themes/bootstrap5/templates/user/user.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("set" => 2, "for" => 12, "if" => 13);
        static $filters = array("escape" => 1, "merge" => 14);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['set', 'for', 'if'],
                ['escape', 'merge'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
